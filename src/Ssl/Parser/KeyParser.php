<?php

/*
 * This file is part of the ACME PHP library.
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
     * @param Key $key
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

        if (!$rawData) {
            throw new KeyParsingException(sprintf('Fail to parse key with error: %s', openssl_error_string()));
        }

        if (!isset($rawData['type'])) {
            throw new KeyParsingException('Missing expected key "type" in OpenSSL key');
        }

        if (!isset($rawData['key'])) {
            throw new KeyParsingException('Missing expected key "key" in OpenSSL key');
        }

        if (!isset($rawData['bits'])) {
            throw new KeyParsingException('Missing expected key "bits" in OpenSSL key');
        }

        $details = [];

        if ($rawData['type'] === OPENSSL_KEYTYPE_RSA) {
            $details = $rawData['rsa'];
        } elseif ($rawData['type'] === OPENSSL_KEYTYPE_DSA) {
            $details = $rawData['dsa'];
        } elseif ($rawData['type'] === OPENSSL_KEYTYPE_DH) {
            $details = $rawData['dh'];
        }

        return new ParsedKey($key, $rawData['key'], $rawData['bits'], $rawData['type'], $details);
    }
}
