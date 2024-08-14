<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Action;

use AcmePhp\Core\Filesystem\FilesystemFactoryInterface;
use AcmePhp\Core\Filesystem\FilesystemInterface;
use AcmePhp\Ssl\CertificateResponse;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class FilesystemAction extends AbstractAction
{
    public function __construct(
        protected FilesystemOperator $storage,
        protected ContainerInterface $filesystemFactoryLocator = new ServiceLocator([]),
    ) {
    }

    public function handle(array $config, CertificateResponse $response): void
    {
        $this->assertConfiguration($config, ['adapter']);

        /** @var FilesystemFactoryInterface $factory */
        $factory = $this->filesystemFactoryLocator->get($config['adapter']);
        $filesystem = $factory->create($config);

        $files = $this->storage->listContents('.', true);
        /** @var FileAttributes $file */
        foreach ($files as $file) {
            if (str_starts_with(basename($file->path()), '.')) {
                continue;
            }

            $this->mirror($file->type(), $file->path(), $filesystem);
        }
    }

    private function mirror(string $type, string $path, FilesystemInterface $filesystem): void
    {
        if ('dir' === $type) {
            $this->mirrorDirectory($path, $filesystem);

            return;
        }

        $this->mirrorFile($path, $filesystem);
    }

    private function mirrorDirectory(string $path, FilesystemInterface $filesystem): void
    {
        $filesystem->createDir($path);
    }

    private function mirrorFile(string $path, FilesystemInterface $filesystem): void
    {
        $storageContent = $this->storage->read($path);

        if (!\is_string($storageContent)) {
            throw new \RuntimeException(sprintf('File %s could not be read on storage storage', $path));
        }

        $filesystem->write($path, $storageContent);
    }
}
