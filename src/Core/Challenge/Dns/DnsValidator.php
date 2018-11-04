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

use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Exception\AcmeDnsResolutionException;
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
     * @var DnsResolverInterface
     */
    private $dnsResolver;

    /**
     * @param DnsDataExtractor     $extractor
     * @param DnsResolverInterface $dnsResolver
     */
    public function __construct(DnsDataExtractor $extractor = null, DnsResolverInterface $dnsResolver = null)
    {
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
        $this->dnsResolver = null === $dnsResolver ? (LibDnsResolver::isSupported() ? new LibDnsResolver() : new SimpleDnsResolver()) : $dnsResolver;
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

        try {
            return \in_array($recordValue, $this->dnsResolver->getTxtEntries($recordName));
        } catch (AcmeDnsResolutionException $e) {
            return false;
        }
    }
}
