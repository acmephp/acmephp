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

    /** @var CsrPem */
    private $csrPem;

    public function __construct(DistinguishedName $distinguishedName, KeyPair $keyPair, string $csrPem = null)
    {
        $this->distinguishedName = $distinguishedName;
        $this->keyPair = $keyPair;
        $this->csrPem = $csrPem;
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

    /**
     * @return CsrPem
     */
    public function getCsrPem()
    {
        return $this->csrPem;
    }
}
