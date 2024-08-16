<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Http;

/**
 * Encode and decode safely in base64.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Base64SafeEncoder
{
    public function encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function decode(string $input): string
    {
        $result = base64_decode(strtr($input, '-_', '+/'), true);
        if ($result === false) {
            throw new \InvalidArgumentException("Input is not valid base64 or base64url data");
        }
        return $result;
    }
}
