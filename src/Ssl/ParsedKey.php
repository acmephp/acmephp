<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

use Webmozart\Assert\Assert;

/**
 * Represent the content of a parsed key.
 *
 * @see openssl_pkey_get_details
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ParsedKey
{
    /** @var Key */
    private $source;

    /** @var string */
    private $key;

    /** @var int */
    private $bits;

    /** @var int */
    private $type;

    /** @var array */
    private $details;

    public function __construct(Key $source, string $key, int $bits, int $type, array $details = [])
    {
        Assert::stringNotEmpty($key, __CLASS__ . '::$key expected a non empty string. Got: %s');
        Assert::oneOf(
            $type,
            [OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_EC],
            __CLASS__ . '::$type expected one of: %2$s. Got: %s',
        );

        $this->source = $source;
        $this->key = $key;
        $this->bits = $bits;
        $this->type = $type;
        $this->details = $details;
    }

    public function getSource(): Key
    {
        return $this->source;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getBits(): int
    {
        return $this->bits;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function hasDetail(string $name): bool
    {
        return isset($this->details[$name]);
    }

    public function getDetail(string $name)
    {
        Assert::oneOf($name, array_keys($this->details), 'ParsedKey::getDetail() expected one of: %2$s. Got: %s');

        return $this->details[$name];
    }
}
