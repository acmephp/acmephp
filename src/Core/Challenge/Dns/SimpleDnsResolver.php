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
 * Resolves DNS through dns_get_record.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SimpleDnsResolver implements DnsResolverInterface
{
    /**
     * @{@inheritdoc}
     */
    public static function isSupported(): bool
    {
        return \function_exists('dns_get_record');
    }

    /**
     * @{@inheritdoc}
     */
    public function getTxtEntries($domain): array
    {
        $entries = array();
        foreach (dns_get_record($domain, DNS_TXT) as $record) {
            $entries = array_merge($entries, $record['entries']);
        }

        sort($entries);

        return array_unique($entries);
    }
}
