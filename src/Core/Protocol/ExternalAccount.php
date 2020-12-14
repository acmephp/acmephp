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
    /** @var string */
    private $id;

    /** @var string */
    private $hmacKey;

    public function __construct(string $id, string $hmacKey)
    {
        $this->id = $id;
        $this->hmacKey = $hmacKey;
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
