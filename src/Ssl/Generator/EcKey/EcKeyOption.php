<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\EcKey;

use AcmePhp\Ssl\Generator\KeyOption;
use Webmozart\Assert\Assert;

class EcKeyOption implements KeyOption
{
    /** @var string */
    private $curveName;

    public function __construct(string $curveName = 'secp384r1')
    {
        if (\PHP_VERSION_ID < 70100) {
            throw new \LogicException('The generation of ECDSA requires a version of PHP >= 7.1');
        }

        Assert::oneOf($curveName, openssl_get_curve_names(), 'The given curve %s is not supported. Available curves are: %s');

        $this->curveName = $curveName;
    }

    public function getCurveName(): string
    {
        return $this->curveName;
    }
}
