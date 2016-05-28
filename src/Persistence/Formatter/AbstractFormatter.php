<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Formatter;

use AcmePhp\Persistence\Serializer\PemEncoder;
use AcmePhp\Persistence\Serializer\PemNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->serializer = new Serializer(
            [new PemNormalizer(), new GetSetMethodNormalizer()],
            [new PemEncoder(), new JsonEncoder()]
        );
    }

    /**
     * @param mixed $entity
     *
     * @return string
     */
    protected function serializePem($entity)
    {
        return $this->serializer->serialize($entity, PemEncoder::FORMAT);
    }

    /**
     * @param mixed $entity
     *
     * @return string
     */
    protected function serializeJson($entity)
    {
        return $this->serializer->serialize($entity, JsonEncoder::FORMAT);
    }
}
