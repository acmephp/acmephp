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

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\Key;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PemNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        return $object->getPEM();
    }

    public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): object
    {
        return new $class($data);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_object($data) && ($data instanceof Certificate || $data instanceof Key);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return \is_string($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Certificate::class => true,
            Key::class => true,
        ];
    }
}
