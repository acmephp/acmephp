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
use Gaufrette\Filesystem;

/**
 * Adapter for Gaufrette.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class GaufretteAdapter implements AdapterInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($path)
    {
        try {
            if ($this->has($path)) {
                return;
            }

            // Gaufrette does not support directory creation but this trick works on most adapters
            $this->filesystem->write($path.'/', '');
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
