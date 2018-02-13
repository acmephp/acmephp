<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Aws\Route53\Route53Client;

/**
 * ACME DNS solver with automate configuration of a AWS route53.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Route53Solver implements SolverInterface
{
    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @var Route53Client
     */
    private $client;

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
        $recordName = $this->extractor->getRecordName($authorizationChallenge);
        $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

        $zone = $this->getZone($authorizationChallenge->getDomain());

        $this->client->changeResourceRecordSets(
            [
                'ChangeBatch' => [
                    'Changes' => [
                        [
                            'Action'            => 'UPSERT',
                            'ResourceRecordSet' => [
                                'Name'            => $recordName,
                                'ResourceRecords' => [
                                    [
                                        'Value' => sprintf('"%s"', $recordValue),
                                    ],
                                ],
                                'TTL'  => 5,
                                'Type' => 'TXT',
                            ],
                        ],
                    ],
                ],
                'HostedZoneId' => $zone['Id'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);

        $zone = $this->getZone($authorizationChallenge->getDomain());
        $recordSets = $this->client->listResourceRecordSets(
            [
                'HostedZoneId'    => $zone['Id'],
                'StartRecordName' => $recordName,
                'StartRecordType' => 'TXT',
            ]
        );

        $recordSets = array_filter(
            $recordSets['ResourceRecordSets'],
            function ($recordSet) use ($recordName) {
                return $recordSet['Name'] === $recordName && $recordSet['Type'] === 'TXT';
            }
        );

        if (!$recordSets) {
            return;
        }

        $this->client->changeResourceRecordSets(
            [
                'ChangeBatch' => [
                    'Changes' => array_map(
                        function ($recordSet) {
                            return [
                                'Action'            => 'DELETE',
                                'ResourceRecordSet' => $recordSet,
                            ];
                        },
                        $recordSets
                    ),
                ],
                'HostedZoneId' => $zone['Id'],
            ]
        );
    }

    private function getZone($domain)
    {
        $domainParts = explode('.', $domain);
        $domains = array_map(
            function ($index) use ($domainParts) {
                return implode('.', array_slice($domainParts, count($domainParts) - $index));
            },
            range(1, count($domainParts))
        );

        $zones = [];
        $args = [];
        do {
            $resp = $this->client->listHostedZones($args);
            $zones = array_merge($zones, $resp['HostedZones']);
            $args = ['Marker' => $resp['NextMarker']];
        } while ($resp['IsTruncated']);

        $zones = array_column($zones, null, 'Name');
        foreach ($domains as $domain) {
            if (isset($zones[$domain.'.'])) {
                return $zones[$domain.'.'];
            }
        }

        throw new ChallengeFailedException(sprintf('Unable to find a zone for the domain "%s"', $domain));
    }
}
