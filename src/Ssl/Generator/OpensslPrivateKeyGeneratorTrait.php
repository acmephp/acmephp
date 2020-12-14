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
use AcmePhp\Ssl\PrivateKey;

trait OpensslPrivateKeyGeneratorTrait
{
    private function generatePrivateKeyFromOpensslOptions(array $opensslOptions): PrivateKey
    {
        $resource = openssl_pkey_new($opensslOptions);

        if (!$resource) {
            throw new KeyGenerationException(sprintf('OpenSSL key creation failed during generation with error: %s', openssl_error_string()));
        }
        if (!openssl_pkey_export($resource, $privateKey)) {
            throw new KeyPairGenerationException(sprintf('OpenSSL key export failed during generation with error: %s', openssl_error_string()));
        }

        // PHP 8 automatically frees the key instance and deprecates the function
        if (\PHP_VERSION_ID < 80000) {
            openssl_free_key($resource);
        }

        return new PrivateKey($privateKey);
    }
}
