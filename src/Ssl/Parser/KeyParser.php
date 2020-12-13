<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Parser;

use AcmePhp\Ssl\Exception\KeyFormatException;
use AcmePhp\Ssl\Exception\KeyParsingException;
use AcmePhp\Ssl\Key;
use AcmePhp\Ssl\ParsedKey;

/**
 * Parse keys to extract metadata.
 *
 * @author Titouan Galopin
 */
class KeyParser
{
    /**
     * Parse the key.
     *
     * @return ParsedKey
     */
    public function parse(Key $key)
    {
        try {
            $resource = $key->getResource();
        } catch (KeyFormatException $e) {
            throw new KeyParsingException('Fail to load resource for key', 0, $e);
        }

        $rawData = openssl_pkey_get_details($resource);

        // PHP 8 automatically frees the key instance and deprecates the function
        if (\PHP_VERSION_ID < 80000) {
            openssl_free_key($resource);
        }

        if (!\is_array($rawData)) {
            throw new KeyParsingException(sprintf('Fail to parse key with error: %s', openssl_error_string()));
        }

        foreach (['type', 'key', 'bits'] as $requiredKey) {
            if (!isset($rawData[$requiredKey])) {
                throw new KeyParsingException(sprintf('Missing expected key "%s" in OpenSSL key', $requiredKey));
            }
        }

        $details = [];

        if (OPENSSL_KEYTYPE_RSA === $rawData['type']) {
            $details = $rawData['rsa'];
        } elseif (OPENSSL_KEYTYPE_DSA === $rawData['type']) {
            $details = $rawData['dsa'];
        } elseif (OPENSSL_KEYTYPE_DH === $rawData['type']) {
            $details = $rawData['dh'];
        } elseif (OPENSSL_KEYTYPE_EC === $rawData['type']) {
            $details = $rawData['ec'];
        }

        return new ParsedKey($key, $rawData['key'], $rawData['bits'], $rawData['type'], $details);
    }
}
