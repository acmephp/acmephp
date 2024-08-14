<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Exception;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class AcmeDnsResolutionException extends AcmeCoreException
{
    public function __construct($message, ?\Exception $previous = null)
    {
        parent::__construct($message ?? 'An exception was thrown during resolution of DNS', 0, $previous);
    }
}
