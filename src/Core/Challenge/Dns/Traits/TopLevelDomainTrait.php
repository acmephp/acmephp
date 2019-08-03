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

use LayerShifter\TLDDatabase\Exceptions\ParserException;
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
        $parse = $extract->parse(str_replace('*.', '', $domain));
        if (!$parse->isValidDomain()) {
            throw new ParserException($domain.' is not a valid domain', 1);
        }

        return $parse->getRegistrableDomain();
    }
}
