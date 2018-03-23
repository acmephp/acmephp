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

use AcmePhp\Core\Filesystem\FilesystemFactoryInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;

class FlysystemSftpFactory implements FilesystemFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(array $config)
    {
        return new FlysystemAdapter(new Filesystem(new SftpAdapter($config)));
    }
}
