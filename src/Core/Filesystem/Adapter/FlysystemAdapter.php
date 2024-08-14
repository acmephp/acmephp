<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Filesystem\Adapter;

use AcmePhp\Core\Filesystem\FilesystemInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator as FlysystemFilesystemInterface;

class FlysystemAdapter implements FilesystemInterface
{
    public function __construct(
        private readonly FlysystemFilesystemInterface $filesystem,
    ) {
    }

    public function write(string $path, string $content): void
    {
        try {
            $this->filesystem->write($path, $content);
        } catch (FilesystemException $e) {
            throw $this->createRuntimeException($path, 'created', $e);
        }
    }

    public function delete(string $path): void
    {
        $isOnRemote = $this->filesystem->has($path);
        try {
            if ($isOnRemote) {
                $this->filesystem->delete($path);
            }
        } catch (FilesystemException $e) {
            throw $this->createRuntimeException($path, 'deleted', $e);
        }
    }

    public function createDir(string $path): void
    {
        try {
            $this->filesystem->createDirectory($path);
        } catch (FilesystemException $e) {
            throw $this->createRuntimeException($path, 'created', $e);
        }
    }

    private function createRuntimeException(string $path, string $action, FilesystemException $e): \RuntimeException
    {
        return new \RuntimeException(
            sprintf(
                'File %s could not be %s because: %s',
                $path,
                $action,
                $e->getMessage(),
            )
        );
    }
}
