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

use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use PHPUnit\Framework\TestCase;

class CertificateRequestSignerTest extends TestCase
{
    /** @var CertificateRequestSigner */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new CertificateRequestSigner();
    }

    public function testSignCertificateRequestReturnsACertificate()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com',
            'FR',
            'france',
            'Paris',
            'acme',
            'IT',
            'qa@acmephp.com',
            array()
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(new RsaKeyOption(1024));

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertIsString($result);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE REQUEST-----', $result);

        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            array(
                'countryName' => 'FR',
                'stateOrProvinceName' => 'france',
                'localityName' => 'Paris',
                'organizationName' => 'acme',
                'organizationalUnitName' => 'IT',
                'commonName' => 'acmephp.com',
                'emailAddress' => 'qa@acmephp.com',
            ),
            $csrResult
        );
    }

    public function testSignCertificateRequestUseDefaultValues()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com'
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(new RsaKeyOption(1024));

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertIsString($result);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE REQUEST-----', $result);
        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            array(
                'commonName' => 'acmephp.com',
            ),
            $csrResult
        );
    }

    public function testSignCertificateRequestWithSubjectAlternativeNames()
    {
        $dummyDistinguishedName = new DistinguishedName(
            'acmephp.com',
            'FR',
            'france',
            'Paris',
            'acme',
            'IT',
            'qa@acmephp.com',
            array('www.acmephp.com')
        );
        $dummyKeyPair = (new KeyPairGenerator())->generateKeyPair(new RsaKeyOption(1024));

        $result = $this->service->signCertificateRequest(
            new CertificateRequest($dummyDistinguishedName, $dummyKeyPair)
        );
        $this->assertIsString($result);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE REQUEST-----', $result);

        $csrResult = openssl_csr_get_subject($result, false);
        $this->assertSame(
            array(
                'countryName' => 'FR',
                'stateOrProvinceName' => 'france',
                'localityName' => 'Paris',
                'organizationName' => 'acme',
                'organizationalUnitName' => 'IT',
                'commonName' => 'acmephp.com',
                'emailAddress' => 'qa@acmephp.com',
            ),
            $csrResult
        );
    }
}
