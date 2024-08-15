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
 * Represent a SSL Public key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class PublicKey extends Key
{
    public function getResource()
    {
        if (!$resource = openssl_pkey_get_public($this->keyPEM)) {
            throw new KeyFormatException(sprintf('Failed to convert key into resource: %s', openssl_error_string()));
        }

        return $resource;
    }

    public static function fromDER(string $keyDER): self
    {
        Assert::stringNotEmpty($keyDER, __METHOD__ . '::$keyDER should be a non-empty string. Got %s');

        $der = base64_encode($keyDER);
        $lines = str_split($der, 65);
        array_unshift($lines, '-----BEGIN PUBLIC KEY-----');
        $lines[] = '-----END PUBLIC KEY-----';
        $lines[] = '';

        return new self(implode("\n", $lines));
    }

    public function getHPKP(): string
    {
        return base64_encode(hash('sha256', $this->getDER(), true));
    }
}
