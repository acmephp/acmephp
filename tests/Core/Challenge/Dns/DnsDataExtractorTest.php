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

use AcmePhp\Core\Challenge\Dns\DnsDataExtractor;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;

class DnsDataExtractorTest extends TestCase
{
    public function testGetRecordName()
    {
        $domain = 'foo.com';

        $mockEncoder = $this->prophesize(Base64SafeEncoder::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $extractor = new DnsDataExtractor($mockEncoder->reveal());

        $stubChallenge->getDomain()->willReturn($domain);

        $this->assertEquals('_acme-challenge.'.$domain.'.', $extractor->getRecordName($stubChallenge->reveal()));
    }

    public function testGetRecordValue()
    {
        $payload = 'randomPayload';
        $encodedPayload = 'encodedSHA256Payload';

        $mockEncoder = $this->prophesize(Base64SafeEncoder::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $extractor = new DnsDataExtractor($mockEncoder->reveal());

        $stubChallenge->getPayload()->willReturn($payload);

        $mockEncoder->encode(hash('sha256', $payload, true))->willReturn($encodedPayload);

        $this->assertEquals($encodedPayload, $extractor->getRecordValue($stubChallenge->reveal()));
    }
}
