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
    public function normalize($object, ?string $format = null, array $context = [])
    {
        return $object->getPEM();
    }

    public function denormalize($data, $class, ?string $format = null, array $context = [])
    {
        return new $class($data);
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return \is_object($data) && ($data instanceof Certificate || $data instanceof Key);
    }

    public function supportsDenormalization($data, $type, ?string $format = null)
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
