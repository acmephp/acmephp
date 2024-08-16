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

namespace AcmePhp\Core\Exception\Protocol;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ChallengeNotSupportedException extends ProtocolException
{
    public function __construct(?\Exception $previous = null)
    {
        parent::__construct('This ACME server does not expose supported challenge.', $previous);
    }
}
