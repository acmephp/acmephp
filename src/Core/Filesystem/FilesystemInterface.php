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

interface FilesystemInterface
{
    /**
     * Write content to a file.
     *
     * @param string $path
     * @param string $content
     */
    public function write($path, $content);

    /**
     * Delete a file.
     *
     * @param string $path
     */
    public function delete($path);

    /**
     * Delete a directory.
     *
     * @param string $path
     */
    public function createDir($path);
}
