<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Adapter;

use AcmePhp\Persistence\Exception\StorageBackendException;

/**
 * Backend storage adapter.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface AdapterInterface
{
    /**
     * Create a directory.
     *
     * @param string $path
     *
     * @throws StorageBackendException If the creation failed or if a backend storage exception occured.
     *
     * @return void
     */
    public function mkdir($path);

    /**
     * Check whether a file exists.
     *
     * @param string $path The path to check existence of.
     *
     * @throws StorageBackendException If a backend storage exception occured.
     *
     * @return bool
     */
    public function has($path);

    /**
     * Read a file.
     *
     * @param string $path The path to the file.
     *
     * @throws StorageBackendException If the file does not exists or if a backend storage exception occured.
     *
     * @return string|false The file contents or false on failure.
     */
    public function read($path);

    /**
     * Write a new file.
     *
     * @param string $path    The path of the new file.
     * @param string $content The file content.
     *
     * @throws StorageBackendException If the file could not be written or if a backend storage exception occured.
     *
     * @return void
     */
    public function write($path, $content);
}
