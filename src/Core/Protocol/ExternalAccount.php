<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

/**
 * Represent an ACME External Account to be used for External Account Binding.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ExternalAccount
{
    public function __construct(
        private readonly string $id,
        private readonly string $hmacKey,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHmacKey(): string
    {
        return $this->hmacKey;
    }
}
