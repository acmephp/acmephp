<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

use AcmePhp\Ssl\Exception\KeyFormatException;

/**
 * Represent a SSL Private key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class PrivateKey extends Key
{
    /**
     *  {@inheritdoc}
     */
    public function getResource()
    {
        if (!$resource = openssl_pkey_get_private($this->keyPEM)) {
            throw new KeyFormatException(sprintf('Fail to convert key into resource: %s', openssl_error_string()));
        }

        return $resource;
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if (!$resource = openssl_pkey_get_private($this->keyPEM)) {
            throw new KeyFormatException(sprintf('Fail to convert key into resource: %s', openssl_error_string()));
        }

        return new PublicKey(openssl_pkey_get_details($resource)['key']);
    }
}
