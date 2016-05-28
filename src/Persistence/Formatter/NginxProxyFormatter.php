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
 * Formatter creating the structure for https://github.com/jwilder/nginx-proxy.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class NginxProxyFormatter extends AbstractFormatter
{
    /**
     * {@inheritdoc}
     */
    public function createDomainKeyPairFiles($domain, KeyPair $keyPair)
    {
        return [
            'nginx-proxy/'.$domain.'.key' => $this->serializePem($keyPair->getPrivateKey()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainDistinguishedNameFiles($domain, DistinguishedName $distinguishedName)
    {
        return [
            'nginx-proxy/'.$domain.'.json' => $this->serializeJson($distinguishedName),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainCertificateFiles($domain, KeyPair $keyPair, Certificate $certificate)
    {
        $fullChain = [];

        while (null !== $certificate) {
            $fullChain[] = $this->serializePem($certificate);
            $certificate = $certificate->getIssuerCertificate();
        }

        return [
            'nginx-proxy/'.$domain.'.crt' => implode("\n", $fullChain),
        ];
    }
}
