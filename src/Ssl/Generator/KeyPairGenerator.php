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

use AcmePhp\Ssl\Exception\KeyPairGenerationException;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use Webmozart\Assert\Assert;

/**
 * Generate random KeyPair using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class KeyPairGenerator
{
    /**
     * Generate KeyPair.
     *
     * @param int $keySize size of the key
     *
     * @throws KeyPairGenerationException when OpenSSL failed to generate keys
     *
     * @return KeyPair
     */
    public function generateKeyPair($keySize = 4096)
    {
        Assert::integer($keySize, __METHOD__.'::$keySize should be an integer. Got: %s');

        $key = openssl_pkey_new(
            [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => $keySize,
            ]
        );

        if (!$key) {
            throw new KeyPairGenerationException(
                sprintf(
                    'OpenSSL key creation failed during generation with error: %s',
                    openssl_error_string()
                )
            );
        }

        if (!openssl_pkey_export($key, $privateKey)) {
            throw new KeyPairGenerationException(
                sprintf(
                    'OpenSSL key export failed during generation with error: %s',
                    openssl_error_string()
                )
            );
        }

        $details = openssl_pkey_get_details($key);

        if (!\is_array($details)) {
            throw new KeyPairGenerationException(
                sprintf(
                    'OpenSSL key parsing failed during generation with error: %s',
                    openssl_error_string()
                )
            );
        }

        return new KeyPair(
            new PublicKey($details['key']),
            new PrivateKey($privateKey)
        );
    }
}
