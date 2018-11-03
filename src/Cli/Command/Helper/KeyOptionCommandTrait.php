<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command\Helper;

use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
trait KeyOptionCommandTrait
{
    private function createKeyOption($keyType)
    {
        switch (\strtoupper($keyType)) {
            case 'RSA':
                return new RsaKeyOption();
            case 'EC':
                return new EcKeyOption();
            default:
                throw new \InvalidArgumentException(sprintf('The keyType "%s" is not valid. Supported types are: RSA, EC', \strtoupper($keyType)));
        }
    }
}
