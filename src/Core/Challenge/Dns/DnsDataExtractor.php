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

use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * Extract data needed to solve DNS challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DnsDataExtractor
{
    /**
     * @var Base64SafeEncoder
     */
    private $encoder;

    /**
     * @param Base64SafeEncoder $encoder
     */
    public function __construct(Base64SafeEncoder $encoder = null)
    {
        $this->encoder = null === $encoder ? new Base64SafeEncoder() : $encoder;
    }

    /**
     * Retrieves the name of the TXT record to register.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     *
     * @return string
     */
    public function getRecordName(AuthorizationChallenge $authorizationChallenge)
    {
        return sprintf('_acme-challenge.%s.', $authorizationChallenge->getDomain());
    }

    /**
     * Retrieves the value of the TXT record to register.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     *
     * @return string
     */
    public function getRecordValue(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->encoder->encode(hash('sha256', $authorizationChallenge->getPayload(), true));
    }
}
