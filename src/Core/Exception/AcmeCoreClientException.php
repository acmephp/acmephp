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

namespace AcmePhp\Core\Exception;

/**
 * Error reported by the client.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeCoreClientException extends AcmeCoreException
{
    public function __construct($message, ?\Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
