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

use AcmePhp\Ssl\PrivateKey;

/**
 * Generate random RSA private key using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ChainPrivateKeyGenerator implements PrivateKeyGeneratorInterface
{
    /** @var PrivateKeyGeneratorInterface[] */
    private $generators;

    /**
     * @param PrivateKeyGeneratorInterface[] $generators
     */
    public function __construct(iterable $generators)
    {
        $this->generators = $generators;
    }

    public function generatePrivateKey(KeyOption $keyOption): PrivateKey
    {
        foreach ($this->generators as $generator) {
            if ($generator->supportsKeyOption($keyOption)) {
                return $generator->generatePrivateKey($keyOption);
            }
        }

        throw new \LogicException(sprintf('Unable to find a generator for a key option of type %s', \get_class($keyOption)));
    }

    public function supportsKeyOption(KeyOption $keyOption): bool
    {
        foreach ($this->generators as $generator) {
            if ($generator->supportsKeyOption($keyOption)) {
                return true;
            }
        }

        return false;
    }
}
