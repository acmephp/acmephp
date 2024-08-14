<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl;

use PHPUnit\Framework\Assert;

trait AssertsOpenSslResource
{
    /**
     * Asserts that the provided "resource" is an OpenSSL Assymetric Key.
     *
     * On PHP 8+, OpenSSL works with objects. On PHP <8 OpenSSL works with resources.
     *
     * @param resource|\OpenSSLAsymmetricKey $resource
     */
    public function assertIsOpenSslAsymmetricKey($resource): void
    {
        if (PHP_MAJOR_VERSION >= 8) {
            Assert::assertInstanceOf(\OpenSSLAsymmetricKey::class, $resource);
        } else {
            Assert::assertIsResource($resource);
        }
    }
}
