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
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;

/**
 * ACME DNS solver with automate configuration of a Gandi.Net.
 *
 * @author Alexander Obuhovich <aik.bold@gmail.com>
 */
class GandiSolver implements MultipleChallengesSolverInterface, ConfigurableServiceInterface
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
    private $apiKey;

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
     *
     * @param array $config
     */
    public function configure(array $config)
    {
        $this->apiKey = $config['api_key'];
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
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $this->client->request(
                'PUT',
                'https://dns.api.gandi.net/api/v5/domains/'.$topLevelDomain.'/records/'.$subDomain.'/TXT',
                [
                    'headers' => [
                        'X-Api-Key' => $this->apiKey,
                    ],
                    'json' => [
                        'rrset_type' => 'TXT',
                        'rrset_ttl' => 600,
                        'rrset_name' => $subDomain,
                        'rrset_values' => [$recordValue],
                    ],
                ]
            );
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
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $this->client->request(
                'DELETE',
                'https://dns.api.gandi.net/api/v5/domains/'.$topLevelDomain.'/records/'.$subDomain.'/TXT',
                [
                    'headers' => [
                        'X-Api-Key' => $this->apiKey,
                    ],
                ]
            );
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
