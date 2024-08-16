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

/**
 * Resolves DNS.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface DnsResolverInterface
{
    /**
     * Return whether or not the Resolver is supported.
     */
    public static function isSupported(): bool;

    /**
     * Retrieves the list of TXT entries for the given domain.
     */
    public function getTxtEntries(string $domain): array;
}
