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
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Filesystem;

class FlysystemFtpFactory implements FilesystemFactoryInterface
{
    public function create(array $config): FilesystemInterface
    {
        return new FlysystemAdapter(new Filesystem(new Ftp($config)));
    }
}
