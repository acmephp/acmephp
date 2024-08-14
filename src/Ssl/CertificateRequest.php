<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

/**
 * Contains data required to request a certificate.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CertificateRequest
{
    public function __construct(
        private readonly DistinguishedName $distinguishedName,
        private readonly KeyPair $keyPair,
    ) {
    }

    public function getDistinguishedName(): DistinguishedName
    {
        return $this->distinguishedName;
    }

    public function getKeyPair(): KeyPair
    {
        return $this->keyPair;
    }
}
