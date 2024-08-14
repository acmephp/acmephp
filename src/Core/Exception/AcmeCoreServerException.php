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

use Psr\Http\Message\RequestInterface;

/**
 * Error reported by the server.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeCoreServerException extends AcmeCoreException
{
    public function __construct(RequestInterface $request, $message, ?\Exception $previous = null)
    {
        parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
    }
}
