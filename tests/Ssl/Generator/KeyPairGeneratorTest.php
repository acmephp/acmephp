<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl\Generator;

use AcmePhp\Ssl\Generator\DhKey\DhKeyOption;
use AcmePhp\Ssl\Generator\DsaKey\DsaKeyOption;
use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\KeyPair;
use PHPUnit\Framework\TestCase;
use Tests\AcmePhp\Ssl\AssertsOpenSslResource;

class KeyPairGeneratorTest extends TestCase
{
    use AssertsOpenSslResource;

    /** @var KeyPairGenerator */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new KeyPairGenerator();
    }

    public function testGenerateKeyPairGenerateRandomInstanceOfKeyPair()
    {
        $result = $this->service->generateKeyPair(new RsaKeyOption(1024));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertIsOpenSslAsymmetricKey($result->getPublicKey()->getResource());
        $this->assertIsOpenSslAsymmetricKey($result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertEquals(1024, $details['bits']);
        $this->assertArrayHasKey('rsa', $details);

        $this->assertEquals($details['key'], $result->getPublicKey()->getPEM());
    }

    public function testGenerateKeyPairGenerateRandomInstanceOfKeyPairUsingDH()
    {
        $result = $this->service->generateKeyPair(new DhKeyOption(
            'dcf93a0b883972ec0e19989ac5a2ce310e1d37717e8d9571bb7623731866e61ef75a2e27898b057f9891c2e27a639c3f29b60814581cd3b2ca3986d2683705577d45c2e7e52dc81c7a171876e5cea74b1448bfdfaf18828efd2519f14e45e3826634af1949e5b535cc829a483b8a76223e5d490a257f05bdff16f2fb22c583ab',
            '02',
        ));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertIsOpenSslAsymmetricKey($result->getPublicKey()->getResource());
        $this->assertIsOpenSslAsymmetricKey($result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertArrayHasKey('dh', $details);

        $this->assertEquals($details['key'], $result->getPublicKey()->getPEM());
    }

    public function testGenerateKeyPairGenerateRandomInstanceOfKeyPairUsingDSA()
    {
        $result = $this->service->generateKeyPair(new DsaKeyOption(1024));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertIsOpenSslAsymmetricKey($result->getPublicKey()->getResource());
        $this->assertIsOpenSslAsymmetricKey($result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertEquals(1024, $details['bits']);
        $this->assertArrayHasKey('dsa', $details);

        $this->assertEquals($details['key'], $result->getPublicKey()->getPEM());
    }

    /**
     * @requires PHP 7.1
     */
    public function testGenerateKeyPairGenerateRandomInstanceOfKeyPairUsingEC()
    {
        $result = $this->service->generateKeyPair(new EcKeyOption('secp112r1'));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertIsOpenSslAsymmetricKey($result->getPublicKey()->getResource());
        $this->assertIsOpenSslAsymmetricKey($result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertEquals(112, $details['bits']);
        $this->assertArrayHasKey('ec', $details);
        $this->assertEquals('secp112r1', $details['ec']['curve_name']);

        $this->assertEquals($details['key'], $result->getPublicKey()->getPEM());
    }
}
