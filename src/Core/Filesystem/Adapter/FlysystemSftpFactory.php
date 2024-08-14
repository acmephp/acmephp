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
use AcmePhp\Core\Filesystem\FilesystemInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

class FlysystemSftpFactory implements FilesystemFactoryInterface
{
    public function create(array $config): FilesystemInterface
    {
        return new FlysystemAdapter(
            new Filesystem(
                new SftpAdapter(
                    new SftpConnectionProvider(
                        $config['host'],
                        $config['username'],
                        password: $config['password'] ?? null,
                        port: $config['port'] ?? 22,
                    ),
                    $config['root'] ?? '/',
                )
            )
        );
    }
}
