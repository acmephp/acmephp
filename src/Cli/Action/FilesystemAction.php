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
use League\Flysystem\FilesystemInterface as FlysystemFilesystemInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class FilesystemAction extends AbstractAction
{
    /**
     * @var FlysystemFilesystemInterface
     */
    protected $master;
    /**
     * @var ContainerInterface
     */
    protected $filesystemFactoryLocator;

    /**
     * @param FlysystemFilesystemInterface $master
     * @param ContainerInterface           $filesystemFactoryLocator
     */
    public function __construct(
        FlysystemFilesystemInterface $master,
        ContainerInterface $filesystemFactoryLocator = null
    ) {
        $this->filesystemFactoryLocator = $filesystemFactoryLocator = null ? new ServiceLocator([]) : $filesystemFactoryLocator;
        $this->master = $master;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($config, CertificateResponse $response)
    {
        $this->assertConfiguration($config, ['adapter']);

        /** @var FilesystemFactoryInterface $factory */
        $factory = $this->filesystemFactoryLocator->get($config['adapter']);
        $filesystem = $factory->create($config);

        $files = $this->master->listContents('.', true);
        foreach ($files as $file) {
            if (0 === strpos($file['basename'], '.')) {
                continue;
            }

            $this->mirror($file['type'], $file['path'], $filesystem);
        }
    }

    /**
     * @param string              $type
     * @param string              $path
     * @param FilesystemInterface $filesystem
     */
    private function mirror($type, $path, FilesystemInterface $filesystem)
    {
        if ('dir' === $type) {
            $this->mirrorDirectory($path, $filesystem);

            return;
        }

        $this->mirrorFile($path, $filesystem);
    }

    /**
     * @param string              $path
     * @param FilesystemInterface $filesystem
     */
    private function mirrorDirectory($path, FilesystemInterface $filesystem)
    {
        $filesystem->createDir($path);
    }

    /**
     * @param string              $path
     * @param FilesystemInterface $filesystem
     */
    private function mirrorFile($path, FilesystemInterface $filesystem)
    {
        $masterContent = $this->master->read($path);

        if (!\is_string($masterContent)) {
            throw new \RuntimeException(sprintf('File %s could not be read on master storage', $path));
        }

        $filesystem->write($path, $masterContent);
    }
}
