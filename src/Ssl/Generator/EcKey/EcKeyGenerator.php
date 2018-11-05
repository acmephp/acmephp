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
use Webmozart\Assert\Assert;

/**
 * Generate random EC private key using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class EcKeyGenerator implements PrivateKeyGeneratorInterface
{
    use OpensslPrivateKeyGeneratorTrait;

    /**
     * @param EcKeyOption|KeyOption $keyOption
     */
    public function generatePrivateKey(KeyOption $keyOption)
    {
        Assert::isInstanceOf($keyOption, EcKeyOption::class);

        return $this->generatePrivateKeyFromOpensslOptions(
            [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => $keyOption->getCurveName(),
            ]
        );
    }

    public function supportsKeyOption(KeyOption $keyOption)
    {
        return $keyOption instanceof EcKeyOption;
    }
}
