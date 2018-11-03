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

use AcmePhp\Ssl\Exception\KeyGenerationException;
use AcmePhp\Ssl\Exception\KeyPairGenerationException;
use AcmePhp\Ssl\Generator\KeyOption;
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
    /**
     * @param EcKeyOption|KeyOption $keyOption
     */
    public function generatePrivateKey(KeyOption $keyOption)
    {
        Assert::isInstanceOf($keyOption, EcKeyOption::class);

        $resource = openssl_pkey_new(
            [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => $keyOption->getCurveName(),
            ]
        );

        if (!$resource) {
            throw new KeyGenerationException(
                sprintf('OpenSSL key creation failed during generation with error: %s', openssl_error_string())
            );
        }
        if (!openssl_pkey_export($resource, $privateKey)) {
            throw new KeyPairGenerationException(
                sprintf('OpenSSL key export failed during generation with error: %s', openssl_error_string())
            );
        }

        return new PrivateKey($privateKey);
    }

    public function supportsKeyOption(KeyOption $keyOption)
    {
        return $keyOption instanceof EcKeyOption;
    }
}
