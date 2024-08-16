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
    public function testGetTxtEntries()
    {
        DnsMock::register(DnsValidator::class);
        DnsMock::withMockedHosts(
            array(
                'domain.com.' => array(
                    array(
                        'type' => 'A',
                        'ip' => '1.2.3.4',
                    ),
                    array(
                        'type' => 'TXT',
                        'entries' => array('foo'),
                    ),
                    array(
                        'type' => 'TXT',
                        'entries' => array('foo', 'bar'),
                    ),
                ),
                'domain2.com.' => array(
                    array(
                        'type' => 'TXT',
                        'entries' => array('baz'),
                    ),
                ),
            ),
        );

        $resolver = new SimpleDnsResolver();
        $entries = $resolver->getTxtEntries('domain.com.');

        $this->assertEquals(array('bar', 'foo'), $entries);
    }
}
