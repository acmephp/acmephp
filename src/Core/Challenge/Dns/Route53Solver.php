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

use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Aws\Route53\Route53Client;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;

/**
 * ACME DNS solver with automate configuration of a AWS route53.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Route53Solver implements MultipleChallengesSolverInterface
{
    use LoggerAwareTrait;
    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @var Route53Client
     */
    private $client;

    /**
     * @var array
     */
    private $cacheZones;

    /**
     * @param DnsDataExtractor $extractor
     * @param Route53Client    $client
     */
    public function __construct(
        DnsDataExtractor $extractor = null,
        Route53Client $client = null
    ) {
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
        $this->client = null === $client ? new Route53Client([]) : $client;
        $this->logger = new NullLogger();
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

        $changesPerZone = [];
        $authorizationChallengesPerDomain = $this->groupAuthorizationChallengesPerDomain($authorizationChallenges);
        foreach ($authorizationChallengesPerDomain as $domain => $authorizationChallengesForDomain) {
            $zone = $this->getZone($authorizationChallengesForDomain[0]->getDomain());

            $authorizationChallengesPerRecordName = $this->groupAuthorizationChallengesPerRecordName($authorizationChallengesForDomain);
            foreach ($authorizationChallengesPerRecordName as $recordName => $authorizationChallengesForRecordName) {
                $recordValues = array_unique(array_map([$this->extractor, 'getRecordValue'], $authorizationChallengesForRecordName));

                $changesPerZone[$zone['Id']][] = [
                    'Action' => 'UPSERT',
                    'ResourceRecordSet' => [
                        'Name' => $recordName,
                        'ResourceRecords' => array_map(function ($recordValue) {
                            return [
                                'Value' => sprintf('"%s"', $recordValue),
                            ];
                        }, $recordValues),
                        'TTL' => 5,
                        'Type' => 'TXT',
                    ],
                ];
            }
        }

        $records = [];
        foreach ($changesPerZone as $zoneId => $changes) {
            $this->logger->info('Updating route 53 DNS', ['zone' => $zoneId]);
            $records[$zoneId] = $this->client->changeResourceRecordSets(
                [
                    'ChangeBatch' => [
                        'Changes' => $changes,
                    ],
                    'HostedZoneId' => $zoneId,
                ]
            );
        }
        foreach ($records as $zoneId => $record) {
            $this->logger->info('Waiting for Route 53 changes', ['zone' => $zoneId]);
            $this->client->waitUntil('ResourceRecordSetsChanged', ['Id' => $record['ChangeInfo']['Id']]);
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

        $changesPerZone = [];
        $authorizationChallengesPerDomain = $this->groupAuthorizationChallengesPerDomain($authorizationChallenges);
        foreach ($authorizationChallengesPerDomain as $domain => $authorizationChallengesForDomain) {
            $zone = $this->getZone($authorizationChallengesForDomain[0]->getDomain());

            $authorizationChallengesPerRecordName = $this->groupAuthorizationChallengesPerRecordName($authorizationChallengesForDomain);
            foreach ($authorizationChallengesPerRecordName as $recordName => $authorizationChallengesForRecordName) {
                $recordSets = $this->client->listResourceRecordSets(
                    [
                        'HostedZoneId' => $zone['Id'],
                        'StartRecordName' => $recordName,
                        'StartRecordType' => 'TXT',
                    ]
                );

                $recordSets = array_filter(
                    $recordSets['ResourceRecordSets'],
                    function ($recordSet) use ($recordName) {
                        return $recordSet['Name'] === $recordName && 'TXT' === $recordSet['Type'];
                    }
                );

                if (!$recordSets) {
                    return;
                }

                if (!isset($changesPerZone[$zone['Id']])) {
                    $changesPerZone[$zone['Id']] = [];
                }
                $changesPerZone[$zone['Id']] = array_merge($changesPerZone[$zone['Id']], array_map(
                    function ($recordSet) {
                        return [
                            'Action' => 'DELETE',
                            'ResourceRecordSet' => $recordSet,
                        ];
                    },
                    $recordSets
                ));
            }
        }

        foreach ($changesPerZone as $zoneId => $changes) {
            $this->logger->info('Updating route 53 DNS', ['zone' => $zoneId]);
            $this->client->changeResourceRecordSets(
                [
                    'ChangeBatch' => [
                        'Changes' => $changes,
                    ],
                    'HostedZoneId' => $zoneId,
                ]
            );
        }
    }

    /**
     * @param AuthorizationChallenge[] $authorizationChallenges
     *
     * @return AuthorizationChallenge[][]
     */
    private function groupAuthorizationChallengesPerDomain(array $authorizationChallenges)
    {
        $groups = [];
        foreach ($authorizationChallenges as $authorizationChallenge) {
            $groups[$authorizationChallenge->getDomain()][] = $authorizationChallenge;
        }

        return $groups;
    }

    /**
     * @param AuthorizationChallenge[] $authorizationChallenges
     *
     * @return AuthorizationChallenge[][]
     */
    private function groupAuthorizationChallengesPerRecordName(array $authorizationChallenges)
    {
        $groups = [];
        foreach ($authorizationChallenges as $authorizationChallenge) {
            $groups[$this->extractor->getRecordName($authorizationChallenge)][] = $authorizationChallenge;
        }

        return $groups;
    }

    private function getZone($domain)
    {
        $domainParts = explode('.', $domain);
        $domains = array_reverse(array_map(
            function ($index) use ($domainParts) {
                return implode('.', array_slice($domainParts, count($domainParts) - $index));
            },
            range(0, count($domainParts))
        ));

        $zones = $this->getZones();
        foreach ($domains as $cursorDomain) {
            if (isset($zones[$cursorDomain.'.'])) {
                return $zones[$cursorDomain.'.'];
            }
        }

        throw new ChallengeFailedException(sprintf('Unable to find a zone for the domain "%s"', $domain));
    }

    private function getZones()
    {
        if (null !== $this->cacheZones) {
            return $this->cacheZones;
        }

        $zones = [];
        $args = [];
        do {
            $resp = $this->client->listHostedZones($args);
            $zones = array_merge($zones, $resp['HostedZones']);
            $args = ['Marker' => $resp['NextMarker']];
        } while ($resp['IsTruncated']);

        $this->cacheZones = array_column($zones, null, 'Name');

        return $this->cacheZones;
    }
}
