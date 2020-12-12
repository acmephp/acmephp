<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\ConfigurableServiceInterface;
use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Ovh\Api;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;

/**
 * ACME DNS solver with automate configuration of a Gandi.Net.
 *
 * @author Alexander Obuhovich <aik.bold@gmail.com>
 */
class OvhSolver implements MultipleChallengesSolverInterface, ConfigurableServiceInterface
{
    use LoggerAwareTrait;
    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $cacheZones;

    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $appSecret;

    /**
     * @var string
     */
    private $endPoint;

    /**
     * @var string
     */
    private $consumerKey;

    /**
     * @var string
     */
    private $domainDelegation;

    /**
     * @param DnsDataExtractor $extractor
     * @param ClientInterface  $client
     */
    public function __construct(
        DnsDataExtractor $extractor = null,
        ClientInterface $client = null
        ) {
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
        $this->client = null === $client ? new Client() : $client;
        $this->logger = new NullLogger();
    }

    /**
     * Configure the service with a set of configuration.
     */
    public function configure(array $config)
    {
        $this->appKey = $config['app_key'];
        $this->appSecret = $config['app_secret'];
        $this->endPoint = $config['end_point'];
        $this->consumerKey = $config['consumer_key'];
        if (isset($config['domain_delegation']))
            $this->domainDelegation = $config['domain_delegation'];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge)
    {
        return 'dns-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->solveAll([$authorizationChallenge]);
    }

    /**
     * {@inheritdoc}
     */
    public function solveAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $this->logger->debug('Create TXT for '.$authorizationChallenge->getDomain());

            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $client = new Client(['http_errors' => false]);

            $ovh = new Api($this->appKey,
                $this->appSecret,
                $this->endPoint,
                $this->consumerKey,
                $client);

            if ($this->domainDelegation != ''){
                $topLevelDomain = $this->domainDelegation;
            }

            $result = $ovh->post('/domain/zone/'.$topLevelDomain.'/record', [
            'fieldType' => 'TXT',
            'subDomain' => $subDomain,
            'target' => $recordValue,
            'ttl' => 600,
            ]);

            if (isset($result['message']) && '' !== $result['message']) {
                $this->logger->error('OVH Exception Post = '.$result['message'].' '.$topLevelDomain);
                throw new \Exception('OVH Exception Post = '.$result['message'].' '.$topLevelDomain);
            }

            $this->logger->info('Refresh zone for '.$authorizationChallenge->getDomain());
            $result = $ovh->post('/domain/zone/'.$topLevelDomain.'/refresh');

            if (isset($result['message']) && '' !== $result['message']) {
                $this->logger->error('OVH Exception Refresh= '.$result['message'].' '.$topLevelDomain);
                throw new \Exception('OVH Exception Refresh= '.$result['message'].' '.$topLevelDomain);
            }

            $this->logger->debug('Create done.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->cleanupAll([$authorizationChallenge]);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $this->logger->debug('Cleanup record id for '.$authorizationChallenge->getDomain());

            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $client = new Client(['http_errors' => false]);

            $ovh = new Api($this->appKey,
                $this->appSecret,
                $this->endPoint,
                $this->consumerKey,
                $client);
            
            if ($this->domainDelegation != ''){
                $topLevelDomain = $this->domainDelegation;
            }

            $result = $ovh->get('/domain/zone/'.$topLevelDomain.'/record', [
            'fieldType' => 'TXT', // Filter the value of fieldType property (like) (type: zone.NamedResolutionFieldTypeEnum)
            'subDomain' => $subDomain, // Filter the value of subDomain property (like) (type: string)
            ]);

            if (isset($result['message']) && '' !== $result['message']) {
                $this->logger->error('OVH Exception Get= '.$result['message'].' '.$topLevelDomain);
                throw new \Exception('OVH Exception Get= '.$result['message'].' '.$topLevelDomain);
            }

            if (1 === \count($result) && isset($result[0])) {
                $id = $result[0];
                $this->logger->debug('Delete record id for '.$authorizationChallenge->getDomain().' '.$id);
                $result = $ovh->delete('/domain/zone/'.$topLevelDomain.'/record/'.$id);

                if (isset($result['message']) && '' !== $result['message']) {
                    $this->logger->error('OVH Exception Delete= '.$result['message'].' '.$topLevelDomain);
                    throw new \Exception('OVH Exception Delete= '.$result['message'].' '.$topLevelDomain);
                }

                $this->logger->debug('Refresh zone for '.$authorizationChallenge->getDomain());
                $result = $ovh->post('/domain/zone/'.$topLevelDomain.'/refresh');
                if (isset($result['message']) && '' !== $result['message']) {
                    $this->logger->error('OVH Exception Refresh= '.$result['message'].' '.$topLevelDomain);
                    throw new \Exception('OVH Exception Refresh= '.$result['message'].' '.$topLevelDomain);
                }

                $this->logger->debug('Cleanup done');
            }
        }
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getTopLevelDomain($domain)
    {
        return \implode('.', \array_slice(\explode('.', $domain), -2));
    }
}
