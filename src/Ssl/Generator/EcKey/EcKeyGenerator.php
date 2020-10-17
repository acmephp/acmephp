<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\EcKey;

use AcmePhp\Ssl\Generator\KeyOption;
use AcmePhp\Ssl\Generator\OpensslPrivateKeyGeneratorTrait;
use AcmePhp\Ssl\Generator\PrivateKeyGeneratorInterface;
use AcmePhp\Ssl\PrivateKey;
use Webmozart\Assert\Assert;

/**
 * Generate random EC private key using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class EcKeyGenerator implements PrivateKeyGeneratorInterface
{
    use OpensslPrivateKeyGeneratorTrait;

    public function generatePrivateKey(KeyOption $keyOption): PrivateKey
    {
        Assert::isInstanceOf($keyOption, EcKeyOption::class);

        return $this->generatePrivateKeyFromOpensslOptions([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $keyOption->getCurveName(),
        ]);
    }

    public function supportsKeyOption(KeyOption $keyOption): bool
    {
        return $keyOption instanceof EcKeyOption;
    }
}
