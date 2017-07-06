<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

/**
 * Represent a SSL key-pair (public and private).
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPair
{
    /** @var PublicKey */
    private $publicKey;

    /** @var PrivateKey */
    private $privateKey;

    /**
     * @param PublicKey  $publicKey
     * @param PrivateKey $privateKey
     */
    public function __construct(PublicKey $publicKey, PrivateKey $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * @param PublicKey $key
     */
    public function setPublicKey(PublicKey $key)
    {
        $this->publicKey = $key;
    }

    /**
     * @param PrivateKey $key
     */
    public function setPrivateKey(PrivateKey $key)
    {
        $this->privateKey = $key;
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return PrivateKey
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }
}
