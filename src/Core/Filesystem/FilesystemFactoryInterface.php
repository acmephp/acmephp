<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Filesystem;

interface FilesystemFactoryInterface
{
    /**
     * Create a new Filesystem.
     *
     * @return FilesystemInterface
     */
    public function create(array $config);
}
