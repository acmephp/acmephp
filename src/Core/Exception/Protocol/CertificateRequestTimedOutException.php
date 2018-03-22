<?php

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
class CertificateRequestTimedOutException extends ProtocolException
{
    public function __construct($response)
    {
        parent::__construct(sprintf('Certificate request timed out (response: %s)', $response));
    }
}
