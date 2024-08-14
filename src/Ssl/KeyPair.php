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
 * Represent a SSL key-pair (public and private).
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPair
{
    public function __construct(
        private readonly PublicKey $publicKey,
        private readonly PrivateKey $privateKey,
    ) {
    }

    public function getPublicKey(): PublicKey
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): PrivateKey
    {
        return $this->privateKey;
    }
}
