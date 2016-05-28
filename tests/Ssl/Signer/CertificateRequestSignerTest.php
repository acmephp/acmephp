<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl\Signer;

use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;

class CertificateRequestSignerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CertificateRequestSigner */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = new CertificateRequestSigner();
    }

    public function test signCertificateRequest returns a certificate()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com',
            'FR', 'france', 'Paris', 'acme', 'IT', 'qa@acmephp.com', []
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(1024);

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertInternalType('string', $result);
        $this->assertContains('-----BEGIN CERTIFICATE REQUEST-----', $result);

        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            [
                'commonName'             => 'acmephp.com',
                'countryName'            => 'FR',
                'stateOrProvinceName'    => 'france',
                'localityName'           => 'Paris',
                'organizationName'       => 'acme',
                'organizationalUnitName' => 'IT',
                'emailAddress'           => 'qa@acmephp.com',

            ],
            $csrResult
        );
    }

    public function test signCertificateRequest use default values()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com'
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(1024);

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertInternalType('string', $result);
        $this->assertContains('-----BEGIN CERTIFICATE REQUEST-----', $result);
        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            [
                'commonName' => 'acmephp.com',

            ],
            $csrResult
        );
    }

    public function test signCertificateRequest with subject alternative names()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com',
            'FR', 'france', 'Paris', 'acme', 'IT', 'qa@acmephp.com', ['www.acmephp.com']
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(1024);

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertInternalType('string', $result);
        $this->assertContains('-----BEGIN CERTIFICATE REQUEST-----', $result);

        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            [
                'commonName'             => 'acmephp.com',
                'countryName'            => 'FR',
                'stateOrProvinceName'    => 'france',
                'localityName'           => 'Paris',
                'organizationName'       => 'acme',
                'organizationalUnitName' => 'IT',
                'emailAddress'           => 'qa@acmephp.com',

            ],
            $csrResult
        );
    }
}
