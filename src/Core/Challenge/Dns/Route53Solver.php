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

    public function __construct(?DnsDataExtractor $extractor = null, ?Route53Client $client = null)
    {
        $this->extractor = $extractor ?: new DnsDataExtractor();
        $this->client = $client ?: new Route53Client([]);
        $this->logger = new NullLogger();
    }

    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'dns-01' === $authorizationChallenge->getType();
    }

    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->solveAll([$authorizationChallenge]);
    }

    public function solveAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        $changesPerZone = [];
        $authorizationChallengesPerDomain = $this->groupAuthorizationChallengesPerDomain($authorizationChallenges);
        foreach ($authorizationChallengesPerDomain as $domain => $authorizationChallengesForDomain) {
            $zone = $this->getZone($authorizationChallengesForDomain[0]->getDomain());

            $authorizationChallengesPerRecordName = $this->groupAuthorizationChallengesPerRecordName($authorizationChallengesForDomain);
            foreach ($authorizationChallengesPerRecordName as $recordName => $authorizationChallengesForRecordName) {
                $challengeValues = array_unique(array_map([$this->extractor, 'getRecordValue'], $authorizationChallengesForRecordName));
                $recordIndex = $this->getPreviousRecordIndex($zone['Id'], $recordName);

                if (0 === \count(array_diff($challengeValues, array_keys($recordIndex)))) {
                    $this->logger->debug('Record already defined', ['recordName' => $recordName]);
                    continue;
                }

                foreach ($challengeValues as $recordValue) {
                    $recordIndex[$recordValue] = time();
                }

                $changesPerZone[$zone['Id']][] = $this->getSaveRecordQuery($recordName, $recordIndex);
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
                ],
            );
        }
        foreach ($records as $zoneId => $record) {
            $this->logger->info('Waiting for Route 53 changes', ['zone' => $zoneId]);
            $this->client->waitUntil('ResourceRecordSetsChanged', ['Id' => $record['ChangeInfo']['Id']]);
        }
    }

    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->cleanupAll([$authorizationChallenge]);
    }

    public function cleanupAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        $changesPerZone = [];
        $authorizationChallengesPerDomain = $this->groupAuthorizationChallengesPerDomain($authorizationChallenges);
        foreach ($authorizationChallengesPerDomain as $domain => $authorizationChallengesForDomain) {
            $zone = $this->getZone($authorizationChallengesForDomain[0]->getDomain());

            $authorizationChallengesPerRecordName = $this->groupAuthorizationChallengesPerRecordName($authorizationChallengesForDomain);
            foreach ($authorizationChallengesPerRecordName as $recordName => $authorizationChallengesForRecordName) {
                $challengeValues = array_unique(array_map([$this->extractor, 'getRecordValue'], $authorizationChallengesForRecordName));
                $recordIndex = $this->getPreviousRecordIndex($zone['Id'], $recordName);

                foreach ($challengeValues as $recordValue) {
                    unset($recordIndex[$recordValue]);
                }
                $changesPerZone[$zone['Id']][] = $this->getSaveRecordQuery($recordName, $recordIndex);
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
                ],
            );
        }
    }

    private function getPreviousRecordIndex($zoneId, $recordName)
    {
        $previousRecordSets = $this->client->listResourceRecordSets([
            'HostedZoneId' => $zoneId,
            'StartRecordName' => $recordName,
            'StartRecordType' => 'TXT',
        ]);
        $recordSets = array_filter(
            $previousRecordSets['ResourceRecordSets'],
            function ($recordSet) use ($recordName) {
                return $recordSet['Name'] === $recordName && 'TXT' === $recordSet['Type'];
            },
        );
        $recordIndex = [];
        foreach ($recordSets as $previousRecordSet) {
            $previousTxt = array_map(function ($resourceRecord) {
                return stripslashes(trim($resourceRecord['Value'], '"'));
            }, $previousRecordSet['ResourceRecords']);
            // Search the special Index
            foreach ($previousTxt as $index => $recordValue) {
                if (null !== $previousIndex = json_decode($recordValue, true)) {
                    $recordIndex = $previousIndex;
                    unset($previousTxt[$index]);
                    break;
                }
            }
            // Set default value
            foreach ($previousTxt as $recordValue) {
                if (!isset($recordIndex[$recordValue])) {
                    $recordIndex[$recordValue] = time();
                }
            }
        }

        return $recordIndex;
    }

    private function getSaveRecordQuery($recordName, array $recordIndex)
    {
        // remove old indexes
        $limitTime = time() - 86400;
        foreach ($recordIndex as $recordValue => $time) {
            if ($time < $limitTime) {
                unset($recordIndex[$recordValue]);
            }
        }

        $recordValues = array_keys($recordIndex);
        $recordValues[] = json_encode($recordIndex);

        return [
            'Action' => 'UPSERT',
            'ResourceRecordSet' => [
                'Name' => $recordName,
                'ResourceRecords' => array_map(function ($recordValue) {
                    return [
                        'Value' => sprintf('"%s"', addslashes($recordValue)),
                    ];
                }, $recordValues),
                'TTL' => 5,
                'Type' => 'TXT',
            ],
        ];
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
                return implode('.', \array_slice($domainParts, \count($domainParts) - $index));
            },
            range(0, \count($domainParts)),
        ));

        $zones = $this->getZones();
        foreach ($domains as $cursorDomain) {
            if (isset($zones[$cursorDomain . '.'])) {
                return $zones[$cursorDomain . '.'];
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
