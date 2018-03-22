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

/**
 * Resolves DNS.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface DnsResolverInterface
{
    /**
     * Retrieves the list of TXT entries for the given domain.
     *
     * @param string $domain
     *
     * @return array
     */
    public function getTxtEntries($domain);

    /**
     * Return whether or not the Resolver is supported.
     *
     * @return bool
     */
    public static function isSupported();
}
