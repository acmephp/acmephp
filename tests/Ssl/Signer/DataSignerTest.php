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

use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\Signer\DataSigner;

class DataSignerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DataSigner */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = new DataSigner();
    }

    public function test signData returns a signature()
    {
        $privateKey = new PrivateKey('-----BEGIN PRIVATE KEY-----
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
jIsyJPXjdAhzAparBWwYzxywy+8PMA==
-----END PRIVATE KEY-----
');

        $this->assertEquals(512, strlen($this->service->signData('foo', $privateKey)));
        $this->assertEquals(512, strlen($this->service->signData('foo', $privateKey, OPENSSL_ALGO_SHA256)));
        $this->assertEquals(512, strlen($this->service->signData('foo', $privateKey, OPENSSL_ALGO_SHA512)));
        $this->assertEquals(
            $this->service->signData('foo', $privateKey),
            $this->service->signData('foo', $privateKey, OPENSSL_ALGO_SHA256)
        );
        $this->assertNotEquals(
            $this->service->signData('foo', $privateKey, OPENSSL_ALGO_SHA256),
            $this->service->signData('foo', $privateKey, OPENSSL_ALGO_SHA512)
        );
    }
}
