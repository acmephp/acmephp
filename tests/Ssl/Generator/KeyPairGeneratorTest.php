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

use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\KeyPair;
use PHPUnit\Framework\TestCase;

class KeyPairGeneratorTest extends TestCase
{
    /** @var KeyPairGenerator */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = new KeyPairGenerator();
    }

    /**
     * @group legacy
     */
    public function test generateKeyPair supports keysize()
    {
        $result = $this->service->generateKeyPair(1024);
        $this->assertInstanceOf(KeyPair::class, $result);
    }

    public function test generateKeyPair generate random instance of KeyPair()
    {
        $result = $this->service->generateKeyPair(new RsaKeyOption(1024));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertContains('-----BEGIN PUBLIC KEY-----', $result->getPublicKey()->getPEM());
        $this->assertContains('-----BEGIN PRIVATE KEY-----', $result->getPrivateKey()->getPEM());
        $this->assertInternalType('resource', $result->getPublicKey()->getResource());
        $this->assertInternalType('resource', $result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertEquals(1024, $details['bits']);
        $this->assertArrayHasKey('rsa', $details);
    }

    /**
     * @requires PHP 7.1
     */
    public function test generateKeyPair generate random instance of KeyPair using EC()
    {
        $result = $this->service->generateKeyPair(new EcKeyOption('secp112r1'));

        $this->assertInstanceOf(KeyPair::class, $result);
        $this->assertContains('-----BEGIN PUBLIC KEY-----', $result->getPublicKey()->getPEM());
        $this->assertContains('-----BEGIN EC PRIVATE KEY-----', $result->getPrivateKey()->getPEM());
        $this->assertInternalType('resource', $result->getPublicKey()->getResource());
        $this->assertInternalType('resource', $result->getPrivateKey()->getResource());

        $details = openssl_pkey_get_details($result->getPrivateKey()->getResource());
        $this->assertEquals(112, $details['bits']);
        $this->assertArrayHasKey('ec', $details);
        $this->assertEquals('secp112r1', $details['ec']['curve_name']);
    }
}
