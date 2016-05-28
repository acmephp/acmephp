<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Formatter;

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * Formatter using the ACME PHP convention.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DefaultFormatter extends AbstractFormatter implements AccountFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function createAccountKeyPairFiles(KeyPair $keyPair)
    {
        return [
            'private/_account/private.pem' => $this->serializePem($keyPair->getPrivateKey()),
            'private/_account/public.pem'  => $this->serializePem($keyPair->getPublicKey()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainKeyPairFiles($domain, KeyPair $keyPair)
    {
        return [
            'private/'.$domain.'/private.pem' => $this->serializePem($keyPair->getPrivateKey()),
            'private/'.$domain.'/public.pem'  => $this->serializePem($keyPair->getPublicKey()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainDistinguishedNameFiles($domain, DistinguishedName $distinguishedName)
    {
        return [
            'certs/'.$domain.'/certificate_request.json' => $this->serializeJson($distinguishedName),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainCertificateFiles($domain, KeyPair $keyPair, Certificate $certificate)
    {
        // Simple certificate
        $certPem = $this->serializePem($certificate);

        // Issuer chain
        $chainPem = [];
        $issuerCertificate = $certificate->getIssuerCertificate();

        while (null !== $issuerCertificate) {
            $issuerChain[] = $this->serializePem($issuerCertificate);
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }

        $chainPem = implode("\n", $chainPem);

        // Full chain
        $fullChainPem = $certPem.$chainPem;

        // Combined
        $combinedPem = $fullChainPem.$this->serializePem($keyPair);

        return [
            'certs/'.$domain.'/cert.pem'      => $certPem,
            'certs/'.$domain.'/chain.pem'     => $chainPem,
            'certs/'.$domain.'/fullchain.pem' => $fullChainPem,
            'certs/'.$domain.'/combined.pem'  => $combinedPem,
        ];
    }
}
