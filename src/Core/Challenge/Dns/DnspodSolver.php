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
use function GuzzleHttp\json_decode;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use QcloudApi;
use Webmozart\Assert\Assert;

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
         * @var \QcloudApi_Module_Cns
         */
        $cns = QcloudApi::load(QcloudApi::MODULE_CNS, $config);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $recordType = $this->extractor->getRecordType($authorizationChallenge);

            if (strtolower($recordType) == 'cname') {
                // 因为 DNSPod 免费版套餐 同一名称 cname 不能并存
                // 所以要删除旧的

                $cns->RecordList([
                    'domain' => $topLevelDomain,
                    'subDomain' => $subDomain,
                    'recordType' => $recordType,
                ]);
                $data = json_decode($cns->getLastResponse(), true);
                if ($data && isset($data['data']) && isset($data['data']['records']) && is_array($data['data']['records']) && count($data['data']['records'])) {
                    $existedRecord = $data['data']['records'][0];
                    if (isset($existedRecord['id'])) {
                        $cns->RecordDelete([
                            'domain' => $topLevelDomain,
                            'recordId' => $existedRecord['id'],
                        ]);
                    }
                }
            }
            $solve = $cns->RecordCreate([
                'domain' => $topLevelDomain,
                'subDomain' => $subDomain,
                'recordType' => $recordType,
                'recordLine' => '默认',
                'value' => $recordValue,
            ]);

            if (false === $solve) {
                /**
                 * @var \QcloudApi_Common_Error
                 */
                $err = $cns->getError();
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
