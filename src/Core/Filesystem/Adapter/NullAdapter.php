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

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class NullAdapter extends FlysystemAdapter
{
    public function __construct()
    {
        parent::__construct(new Filesystem(new InMemoryFilesystemAdapter()));
    }
}
