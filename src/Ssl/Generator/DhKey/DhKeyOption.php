<?php

declare(strict_types=1);

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\DhKey;

use AcmePhp\Ssl\Generator\KeyOption;

class DhKeyOption implements KeyOption
{
    /** @var string */
    private $generator;

    /** @var string */
    private $prime;

    /**
     * @param string $prime     Hexadecimal representation of the prime
     * @param string $generator Hexadecimal representation of the generator: ie. 02
     *
     * @see https://tools.ietf.org/html/rfc3526 how to choose a prime and generator numbers
     */
    public function __construct(string $prime, string $generator = '02')
    {
        $this->generator = pack('H*', $generator);
        $this->prime = pack('H*', $prime);
    }

    public function getGenerator(): string
    {
        return $this->generator;
    }

    public function getPrime(): string
    {
        return $this->prime;
    }
}
