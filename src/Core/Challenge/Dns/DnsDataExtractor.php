<?php

declare(strict_types=1);

/*
 * This file is part of the Acme PHP project.
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
    /** @var Base64SafeEncoder */
    private $encoder;

    public function __construct(?Base64SafeEncoder $encoder = null)
    {
        $this->encoder = $encoder ?: new Base64SafeEncoder();
    }

    /**
     * Retrieves the name of the TXT record to register.
     */
    public function getRecordName(AuthorizationChallenge $authorizationChallenge): string
    {
        return sprintf('_acme-challenge.%s.', $authorizationChallenge->getDomain());
    }

    /**
     * Retrieves the value of the TXT record to register.
     */
    public function getRecordValue(AuthorizationChallenge $authorizationChallenge): string
    {
        return $this->encoder->encode(hash('sha256', $authorizationChallenge->getPayload(), true));
    }
}
