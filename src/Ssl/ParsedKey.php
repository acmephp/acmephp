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

    /**
     * @param Key    $source
     * @param string $key
     * @param int    $bits
     * @param int    $type
     * @param array  $details
     */
    public function __construct(Key $source, $key, $bits, $type, array $details = [])
    {
        Assert::stringNotEmpty($key, __CLASS__.'::$key expected a non empty string. Got: %s');
        Assert::integer($bits, __CLASS__.'::$bits expected an integer. Got: %s');
        Assert::oneOf(
            $type,
            [OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_EC],
            __CLASS__.'::$type expected one of: %2$s. Got: %s'
        );

        $this->source = $source;
        $this->key = $key;
        $this->bits = $bits;
        $this->type = $type;
        $this->details = $details;
    }

    /**
     * @return Key
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasDetail($name)
    {
        return isset($this->details[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getDetail($name)
    {
        Assert::oneOf($name, array_keys($this->details), 'ParsedKey::getDetail() expected one of: %2$s. Got: %s');

        return $this->details[$name];
    }
}
