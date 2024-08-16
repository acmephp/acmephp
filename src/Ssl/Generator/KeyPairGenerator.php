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
use AcmePhp\Ssl\Exception\KeyPairGenerationException;
use AcmePhp\Ssl\Generator\DhKey\DhKeyGenerator;
use AcmePhp\Ssl\Generator\DsaKey\DsaKeyGenerator;
use AcmePhp\Ssl\Generator\EcKey\EcKeyGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\KeyPair;

/**
 * Generate random KeyPair using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class KeyPairGenerator
{
    private $generator;

    public function __construct(?PrivateKeyGeneratorInterface $generator = null)
    {
        $this->generator = $generator ?: new ChainPrivateKeyGenerator(
            array(
                new RsaKeyGenerator(),
                new EcKeyGenerator(),
                new DhKeyGenerator(),
                new DsaKeyGenerator(),
            ),
        );
    }

    /**
     * @param KeyOption|null $keyOption configuration of the key to generate
     *
     * @throws KeyPairGenerationException when OpenSSL failed to generate keys
     */
    public function generateKeyPair(?KeyOption $keyOption = null): KeyPair
    {
        if (null === $keyOption) {
            $keyOption = new RsaKeyOption();
        }

        try {
            $privateKey = $this->generator->generatePrivateKey($keyOption);
        } catch (KeyGenerationException $e) {
            throw new KeyPairGenerationException('Fail to generate a KeyPair with the given options', 0, $e);
        }

        return new KeyPair(
            $privateKey->getPublicKey(),
            $privateKey,
        );
    }
}
