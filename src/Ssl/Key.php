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
 * Represent a SSL key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
abstract class Key
{
    /** @var string */
    protected $keyPEM;

    /**
     * @param string $keyPEM
     */
    public function __construct($keyPEM)
    {
        Assert::stringNotEmpty($keyPEM, __CLASS__.'::$keyPEM should not be an empty string. Got %s');

        $this->keyPEM = $keyPEM;
    }

    /**
     * @return string
     */
    public function getPEM()
    {
        return $this->keyPEM;
    }

    /**
     * @return string
     */
    public function getDER()
    {
        $lines = explode("\n", trim($this->keyPEM));
        unset($lines[\count($lines) - 1]);
        unset($lines[0]);
        $result = implode('', $lines);
        $result = base64_decode($result);

        return $result;
    }

    /**
     * @return resource
     */
    abstract public function getResource();
}
