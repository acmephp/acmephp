<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Util;

/**
 * Guzzle HTTP client wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class JsonDecoder
{
    /**
     * Wrapper for json_decode that throws when an error occurs.
     * Extracted from Guzzle for BC.
     *
     * @param string $json    JSON data to parse
     * @param bool   $assoc   when true, returned objects will be converted
     *                        into associative arrays
     * @param int    $depth   user specified recursion depth
     * @param int    $options bitmask of JSON decode options
     *
     * @throws \InvalidArgumentException if the JSON cannot be decoded
     *
     * @return mixed
     *
     * @see http://www.php.net/manual/en/function.json-decode.php
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('json_decode error: '.json_last_error_msg());
        }

        return $data;
    }
}
