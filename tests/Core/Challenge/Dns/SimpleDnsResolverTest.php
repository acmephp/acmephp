<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\Dns\DnsValidator;
use AcmePhp\Core\Challenge\Dns\SimpleDnsResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\DnsMock;

class SimpleDnsResolverTest extends TestCase
{
    public function testGetTxtEntries(): void
    {
        DnsMock::register(DnsValidator::class);
        DnsMock::withMockedHosts(
            [
                'domain.com.' => [
                    [
                        'type' => 'A',
                        'ip' => '1.2.3.4',
                    ],
                    [
                        'type' => 'TXT',
                        'entries' => ['foo'],
                    ],
                    [
                        'type' => 'TXT',
                        'entries' => ['foo', 'bar'],
                    ],
                ],
                'domain2.com.' => [
                    [
                        'type' => 'TXT',
                        'entries' => ['baz'],
                    ],
                ],
            ]
        );

        $resolver = new SimpleDnsResolver();
        $entries = $resolver->getTxtEntries('domain.com.');

        $this->assertEquals(['bar', 'foo'], $entries);
    }
}
