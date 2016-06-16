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

use AcmePhp\Ssl\CertificateResponse;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractFlysystemAction extends AbstractAction
{
    /**
     * @var FilesystemInterface
     */
    protected $master;

    /**
     * @param FilesystemInterface $master
     */
    public function __construct(FilesystemInterface $master)
    {
        $this->master = $master;
    }

    /**
     * @param array $config
     *
     * @return AdapterInterface
     */
    abstract protected function createAdapter($config);

    /**
     * @param AdapterInterface $adapter
     *
     * @return string
     */
    abstract protected function getLastError(AdapterInterface $adapter);

    /**
     * {@inheritdoc}
     */
    public function handle($config, CertificateResponse $response)
    {
        $remoteAdapter = $this->createAdapter($config);
        $remote = new Filesystem($remoteAdapter);

        $files = $this->master->listContents('.', true);

        foreach ($files as $file) {
            if (0 === strpos($file['basename'], '.')) {
                continue;
            }

            $this->mirror($file['type'], $file['path'], $remote, $remoteAdapter);
        }
    }

    /**
     * @param string              $type
     * @param string              $path
     * @param FilesystemInterface $remote
     * @param AdapterInterface    $remoteAdapter
     */
    private function mirror($type, $path, FilesystemInterface $remote, AdapterInterface $remoteAdapter)
    {
        if ($type === 'dir' && !$remote->has($path)) {
            if (!$remote->createDir($path)) {
                throw $this->createRuntimeException('Directory', $path, 'created', $remoteAdapter);
            }

            return;
        }

        $masterContent = $this->master->read($path);

        if (!is_string($masterContent)) {
            throw new \RuntimeException(sprintf('File %s could not be read on master storage', $path));
        }

        if ($remote->has($path)) {
            if (!$remote->update($path, $masterContent)) {
                throw $this->createRuntimeException('File', $path, 'updated', $remoteAdapter);
            }

            return;
        }

        if (!$remote->write($path, $masterContent)) {
            throw $this->createRuntimeException('File', $path, 'created', $remoteAdapter);
        }
    }

    /**
     * @param string           $type
     * @param string           $path
     * @param string           $action
     * @param AdapterInterface $adapter
     *
     * @return \RuntimeException
     */
    private function createRuntimeException($type, $path, $action, AdapterInterface $adapter)
    {
        return new \RuntimeException(sprintf(
            '%s %s could not be %s on remote host (backend error: %s)',
            $type,
            $path,
            $action,
            $this->getLastError($adapter)
        ));
    }
}
