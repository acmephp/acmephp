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

use AcmePhp\Ssl\Exception\KeyFormatException;
use Webmozart\Assert\Assert;

/**
 * Represent a SSL Private key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class PrivateKey extends Key
{
    public function getResource()
    {
        if (!$resource = openssl_pkey_get_private($this->keyPEM)) {
            throw new KeyFormatException(sprintf('Failed to convert key into resource: %s', openssl_error_string()));
        }

        return $resource;
    }

    public function getPublicKey(): PublicKey
    {
        $resource = $this->getResource();
        if (!$details = openssl_pkey_get_details($resource)) {
            throw new KeyFormatException(sprintf('Failed to extract public key: %s', openssl_error_string()));
        }

        // PHP 8 automatically frees the key instance and deprecates the function
        if (\PHP_VERSION_ID < 80000) {
            openssl_free_key($resource);
        }

        return new PublicKey($details['key']);
    }

    public static function fromDER(string $keyDER): self
    {
        Assert::stringNotEmpty($keyDER, __METHOD__ . '::$keyDER should be a non-empty string. Got %s');

        $der = base64_encode($keyDER);
        $lines = str_split($der, 65);
        array_unshift($lines, '-----BEGIN PRIVATE KEY-----');
        $lines[] = '-----END PRIVATE KEY-----';
        $lines[] = '';

        return new self(implode("\n", $lines));
    }
}
