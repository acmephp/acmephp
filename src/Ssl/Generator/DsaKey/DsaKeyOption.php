<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\DsaKey;

use AcmePhp\Ssl\Generator\KeyOption;

class DsaKeyOption implements KeyOption
{
    public function __construct(
        private readonly int $bits = 2048,
    ) {
    }

    public function getBits(): int
    {
        return $this->bits;
    }
}
