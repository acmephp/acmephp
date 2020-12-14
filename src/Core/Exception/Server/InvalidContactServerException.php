<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Exception\Server;

use AcmePhp\Core\Exception\AcmeCoreServerException;
use Psr\Http\Message\RequestInterface;

/**
 * @author Alex Plekhanov <alex@plekhanov.dev>
 */
class InvalidContactServerException extends AcmeCoreServerException
{
    public function __construct(RequestInterface $request, string $detail, \Exception $previous = null)
    {
        parent::__construct(
            $request,
            '[invalidContact] A contact URL for an account was invalid: '.$detail,
            $previous
        );
    }
}
