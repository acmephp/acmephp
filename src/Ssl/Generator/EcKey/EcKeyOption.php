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

    public function __construct($curveName = 'secp384r1')
    {
        Assert::stringNotEmpty($curveName);
        Assert::oneOf($curveName, openssl_get_curve_names(), 'The given curve %s is not supported. Available curves are: %s');

        $this->curveName = $curveName;
    }

    /**
     * @return string
     */
    public function getCurveName()
    {
        return $this->curveName;
    }
}
