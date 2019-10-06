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
use AlibabaCloud\Alidns\Alidns;
use AlibabaCloud\Client\AlibabaCloud;

/**
 * ACME DNS solver with automate configuration of a Aliyun.com
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class AliyunSolver implements MultipleChallengesSolverInterface, ConfigurableServiceInterface
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
    private $accessKeyId;

    /**
     * @var string
     */
    private $accessKeySecret;

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
        $this->accessKeyId = $config['access_key_id'];
        $this->accessKeySecret = $config['access_key_secret'];

        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)->regionId('cn-hangzhou')->asDefaultClient();
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
            $recordType = $authorizationChallenge->getPath();
            if (!$recordType) {
                $recordType = 'TXT';
            }

            $subDomain = \str_replace('.' . $topLevelDomain . '.', '', $recordName);

            $dns = new Alidns();

            if (strtolower($recordType) == 'cname') {
                try {
                    /**
                     * @var \AlibabaCloud\Client\Result\Result $list
                     */
                    $list = $dns->v20150109()->describeSubDomainRecords()
                        ->withSubDomain($subDomain . '.' . $topLevelDomain)
                        ->withType($recordType)
                        ->withPageSize(100)
                        ->request();

                    $records = $list->get('DomainRecords');
                    $records = isset($records['Record']) ? $records['Record'] : $records;

                    foreach ($records as $record) {
                        try {
                            $recordId = $record['RecordId'];
                            $dns->v20150109()->deleteDomainRecord()
                                ->withRecordId($recordId)
                                ->request();
                        } catch (\Exception $e) {
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            /**
             * @var \AlibabaCloud\Client\Result\Result $response
             */
            $response = $dns->v20150109()->addDomainRecord()
                ->withDomainName($topLevelDomain)
                ->withType(isset($authorizationChallenge['dnsType']) ? $authorizationChallenge['dnsType'] : 'TXT')
                ->withRR($subDomain)
                ->withValue($recordValue)
                ->request();

            /**
             * Store to $authorizationChallenge, because when it requires recordId when clear
             */
            $authorizationChallenge->recordId = $response->get('RecordId');
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
            $dns = new Alidns();
            $dns->v20150109()->deleteDomainRecord()
                ->withRecordId($authorizationChallenge->recordId)
                ->request();
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
