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
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Adapter for the Symfony Filesystem component.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class LocalAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string          $rootDirectory
     * @param Filesystem|null $filesystem
     */
    public function __construct($rootDirectory, Filesystem $filesystem = null)
    {
        $this->rootDirectory = rtrim($rootDirectory, '\\/').'/';
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($path)
    {
        try {
            $this->filesystem->mkdir($this->canonicalize($path));
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
            return $this->filesystem->exists($this->canonicalize($path));
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
            if ($this->has($path)) {
                throw new \RuntimeException(sprintf('File %s already exists', $path));
            }

            $this->filesystem->dumpFile($this->canonicalize($path), $content);
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
            $canonicalizedPath = $this->canonicalize($path);

            if (!file_exists($canonicalizedPath)) {
                throw new \RuntimeException(sprintf('File %s does not exist', $path));
            }

            if (!is_file($canonicalizedPath)) {
                throw new \RuntimeException(sprintf('Path %s is not a file', $canonicalizedPath));
            }

            if (!is_readable($canonicalizedPath)) {
                throw new \RuntimeException(sprintf('File %s is not readable', $canonicalizedPath));
            }

            $content = @file_get_contents($canonicalizedPath);

            if (false === $content) {
                throw new \RuntimeException(sprintf('File %s could not be read', $canonicalizedPath));
            }

            return $content;
        } catch (\Exception $e) {
            throw new StorageBackendException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($path)
    {
        if ($this->has($path)) {
            try {
                $this->filesystem->remove($this->canonicalize($path));
            } catch (\Exception $e) {
                throw new StorageBackendException($e);
            }
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function canonicalize($path)
    {
        return Path::canonicalize($this->rootDirectory.$path);
    }
}
