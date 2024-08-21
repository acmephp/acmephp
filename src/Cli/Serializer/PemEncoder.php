<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PemEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'pem';

    public function encode(mixed $data, string $format, array $context = []): string
    {
        return trim($data)."\n";
    }

    public function decode(string $data, string $format, array $context = []): string
    {
        return trim($data)."\n";
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
