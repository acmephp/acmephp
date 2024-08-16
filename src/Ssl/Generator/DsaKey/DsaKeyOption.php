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

namespace AcmePhp\Ssl\Generator\DsaKey;

use AcmePhp\Ssl\Generator\KeyOption;

class DsaKeyOption implements KeyOption
{
    /** @var int */
    private $bits;

    public function __construct(int $bits = 2048)
    {
        $this->bits = $bits;
    }

    public function getBits(): int
    {
        return $this->bits;
    }
}
