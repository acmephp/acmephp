<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl\Signer;

use AcmePhp\Ssl\Generator\EcKey\EcKeyGenerator;
use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\Signer\DataSigner;
use PHPUnit\Framework\TestCase;

class DataSignerTest extends TestCase
{
    private DataSigner $service;

    public function setUp(): void
    {
        $this->service = new DataSigner();
    }

    public function testSignDataReturnsASignature(): void
    {
        $privateRsaKey = (new RsaKeyGenerator())->generatePrivateKey(new RsaKeyOption());

        $this->assertEquals(512, \strlen($this->service->signData('foo', $privateRsaKey)));
        $this->assertEquals(512, \strlen($this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA256)));
        $this->assertEquals(512, \strlen($this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA384)));
        $this->assertEquals(512, \strlen($this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA512)));
        $this->assertEquals(
            $this->service->signData('foo', $privateRsaKey),
            $this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA256)
        );
        $this->assertNotEquals(
            $this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA256),
            $this->service->signData('foo', $privateRsaKey, OPENSSL_ALGO_SHA512)
        );
    }

    /**
     * @requires PHP 7.1
     */
    public function testSignDataReturnsASignatureForEcKeys(): void
    {
        $this->assertEquals(64, \strlen($this->service->signData('foo', (new EcKeyGenerator())->generatePrivateKey(new EcKeyOption('prime256v1')), OPENSSL_ALGO_SHA256, DataSigner::FORMAT_ECDSA)));
        $this->assertEquals(96, \strlen($this->service->signData('foo', (new EcKeyGenerator())->generatePrivateKey(new EcKeyOption('secp384r1')), OPENSSL_ALGO_SHA384, DataSigner::FORMAT_ECDSA)));
        $this->assertEquals(132, \strlen($this->service->signData('foo', (new EcKeyGenerator())->generatePrivateKey(new EcKeyOption('secp521r1')), OPENSSL_ALGO_SHA512, DataSigner::FORMAT_ECDSA)));
    }
}
