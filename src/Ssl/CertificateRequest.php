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
    /** @var DistinguishedName */
    private $distinguishedName;

    /** @var KeyPair */
    private $keyPair;

    /**
     * @param DistinguishedName $distinguishedName
     * @param KeyPair           $keyPair
     */
    public function __construct(DistinguishedName $distinguishedName, KeyPair $keyPair)
    {
        $this->distinguishedName = $distinguishedName;
        $this->keyPair = $keyPair;
    }

    /**
     * @return DistinguishedName
     */
    public function getDistinguishedName()
    {
        return $this->distinguishedName;
    }

    /**
     * @return KeyPair
     */
    public function getKeyPair()
    {
        return $this->keyPair;
    }
}
