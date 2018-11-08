<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator;

use AcmePhp\Ssl\Exception\KeyGenerationException;
use AcmePhp\Ssl\PrivateKey;

/**
 * Generate random private key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface PrivateKeyGeneratorInterface
{
    /**
     * Generate a PrivateKey.
     *
     * @param KeyOption $keyOption configuration of the key to generate
     *
     * @throws KeyGenerationException when OpenSSL failed to generate keys
     *
     * @return PrivateKey
     */
    public function generatePrivateKey(KeyOption $keyOption);

    /**
     * Returns whether the instance is able to generator a private key from the given option.
     *
     * @param KeyOption $keyOption configuration of the key to generate
     *
     * @return bool
     */
    public function supportsKeyOption(KeyOption $keyOption);
}
