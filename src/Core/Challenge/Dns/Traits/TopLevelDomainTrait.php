<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns\Traits;

use LayerShifter\TLDExtract\Extract as TLDExtract;

trait TopLevelDomainTrait
{
    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getTopLevelDomain($domain)
    {
        $extract = new TLDExtract();
        $parse = $extract->parse($domain);

        return $parse->getRegistrableDomain();
    }
}
