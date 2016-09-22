<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\Dns\DnsDataExtractor;
use AcmePhp\Core\Challenge\Dns\DnsValidator;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Bridge\PhpUnit\DnsMock;

class DnsValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new DnsValidator($mockExtractor->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertTrue($validator->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertFalse($validator->supports($stubChallenge->reveal()));
    }

    public function testIsValid()
    {
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';
        DnsMock::register(DnsValidator::class);
        DnsMock::withMockedHosts(
            [
                $recordName => [
                    [
                        'type' => 'A',
                        'ip'   => '1.2.3.4',
                    ],
                    [
                        'type'    => 'TXT',
                        'entries' => [
                            $recordValue,
                        ],
                    ],
                ],
            ]
        );

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new DnsValidator($mockExtractor->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);

        $this->assertTrue($validator->isValid($stubChallenge->reveal()));
    }

    public function testIsValidCheckRecordValue()
    {
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';
        DnsMock::register(DnsValidator::class);
        DnsMock::withMockedHosts(
            [
                $recordName => [
                    [
                        'type' => 'A',
                        'ip'   => '1.2.3.4',
                    ],
                    [
                        'type'    => 'TXT',
                        'entries' => [
                            'somethingElse',
                        ],
                    ],
                ],
            ]
        );

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new DnsValidator($mockExtractor->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);

        $this->assertFalse($validator->isValid($stubChallenge->reveal()));
    }
}
