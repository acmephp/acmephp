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
use AcmePhp\Core\Challenge\Dns\Traits\TopLevelDomainTrait;
use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use QcloudApi;
use QcloudApi_Common_Request;
use Webmozart\Assert\Assert;

use function GuzzleHttp\json_decode;

/**
 * ACME DNS solver with automate configuration of a DnsPod.cn (TencentCloud NS).
 *
 * @author Xiaohui Lam <xiaohui.lam@e.hexdata.cn>
 */
class DnspodSolver implements MultipleChallengesSolverInterface, ConfigurableServiceInterface
{
    use LoggerAwareTrait, TopLevelDomainTrait;

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
    private $secretId;

    /**
     * @var string
     */
    private $secretKey;

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
        $this->secretId = $config['secret_id'];
        $this->secretKey = $config['secret_key'];
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

        $config = [
            'SecretId' => $this->secretId,
            'SecretKey' => $this->secretKey,
            'RequestMethod' => 'GET',
            'DefaultRegion' => 'gz',
        ];
        /**
         * @var \QcloudApi_Module_Cns $cns
         */
        $cns = QcloudApi::load(QcloudApi::MODULE_CNS, $config);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $recordType = 'txt';
            if (method_exists($this->extractor, 'getRecordType')) {
                $recordType = $this->extractor->getRecordType($authorizationChallenge);
            }

            if ('cname' === strtolower($recordType)) {
                // Because DNSPod can't create conflicting cname records
                // So we'd delete existing records first
                clear:

                $cns->RecordList([
                    'domain' => $topLevelDomain,
                    'subDomain' => $subDomain,
                    'recordType' => $recordType,
                ]);
                try {
                    $data = json_decode($cns->getLastResponse(), true);
                } catch (InvalidArgumentException $e) {
                    $err = $cns->getError();
                    if ($err) {
                        if ($err->getCode() == 3000) {
                            throw new \Exception('DNSPod Api Exception:' . QcloudApi_Common_Request::getRawResponse(), $err->getCode());
                        }
                        print_r($err->getExt());
                        throw new \Exception($err->getMessage(), $err->getCode());
                    } else {
                        echo $cns->getLastResponse();
                    }
                    throw $e;
                }
                if ($data && isset($data['data']) && isset($data['data']['records']) && \is_array($data['data']['records']) && \count($data['data']['records'])) {
                    foreach ($data['data']['records'] as $existedRecord) {
                        if (isset($existedRecord['id'])) {
                            $cns->RecordDelete([
                                'domain' => $topLevelDomain,
                                'recordId' => $existedRecord['id'],
                            ]);
                        }
                    }
                }
            }
            $solve = $cns->RecordCreate([
                'domain' => $topLevelDomain,
                'subDomain' => $subDomain,
                'recordType' => 'TXT',
                'recordLine' => '默认',
                'value' => $recordValue,
            ]);

            if (false === $solve) {
                /**
                 * @var \QcloudApi_Common_Error
                 */
                $err = $cns->getError();
                if (strpos($err->getMessage(), '子域名负载均衡数量超出限制') !== false) {
                    goto clear;
                }
                throw new Exception($err->getMessage(), $err->getCode());
            }

            $data = json_decode($cns->getLastResponse(), true);
            $authorizationChallenge->recordId = $data['data']['record']['id'];
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

        $config = [
            'SecretId' => $this->secretId,
            'SecretKey' => $this->secretKey,
            'RequestMethod' => 'GET',
            'DefaultRegion' => 'gz',
        ];
        /**
         * @var \QcloudApi_Module_Cns
         */
        $cns = QcloudApi::load(QcloudApi::MODULE_CNS, $config);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());

            $cns->RecordDelete([
                'domain' => $topLevelDomain,
                'recordId' => $authorizationChallenge->recordId,
            ]);
        }
    }
}
