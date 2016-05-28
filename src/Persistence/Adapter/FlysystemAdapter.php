<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Adapter;

use AcmePhp\Persistence\Exception\StorageBackendException;
use League\Flysystem\FilesystemInterface;

/**
 * Adapter for Flysystem.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class FlysystemAdapter implements AdapterInterface
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($path)
    {
        try {
            $this->filesystem->createDir($path);
        } catch (\Exception $e) {
            throw new StorageBackendException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        try {
            return $this->filesystem->has($path);
        } catch (\Exception $e) {
            throw new StorageBackendException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        try {
            return $this->filesystem->read($path);
        } catch (\Exception $e) {
            throw new StorageBackendException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $content)
    {
        try {
            $this->filesystem->write($path, $content);
        } catch (\Exception $e) {
            throw new StorageBackendException($e);
        }
    }
}
