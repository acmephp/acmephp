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

use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * Validator for DNS challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DnsValidator implements ValidatorInterface
{
    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @param DnsDataExtractor $extractor
     */
    public function __construct(DnsDataExtractor $extractor)
    {
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
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
    public function isValid(AuthorizationChallenge $authorizationChallenge)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);
        $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

        foreach (dns_get_record($recordName, DNS_TXT) as $record) {
            if (in_array($recordValue, $record['entries'])) {
                return true;
            }
        }

        return false;
    }
}
