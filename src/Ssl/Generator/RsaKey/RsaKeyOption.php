<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\RsaKey;

use AcmePhp\Ssl\Generator\KeyOption;

class RsaKeyOption implements KeyOption
{
    /** @var int */
    private $bits;

    public function __construct(int $bits = 4096)
    {
        $this->bits = $bits;
    }

    public function getBits(): int
    {
        return $this->bits;
    }
}
