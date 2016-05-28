<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Loader;

use AcmePhp\Persistence\Serializer\PemEncoder;
use AcmePhp\Persistence\Serializer\PemNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractLoader implements LoaderInterface
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
     * @param string $data
     * @param string $class
     *
     * @return mixed
     */
    protected function deserializePem($data, $class)
    {
        return $this->serializer->deserialize($data, $class, PemEncoder::FORMAT);
    }

    /**
     * @param string $data
     * @param string $class
     *
     * @return mixed
     */
    protected function deserializeJson($data, $class)
    {
        return $this->serializer->deserialize($data, $class, JsonEncoder::FORMAT);
    }
}
