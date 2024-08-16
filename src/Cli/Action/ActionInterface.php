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

namespace AcmePhp\Cli\Action;

use AcmePhp\Ssl\CertificateResponse;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface ActionInterface
{
    /**
     * Get a certificate response and execute the action with it.
     * Use the given configuration if needed.
     */
    public function handle(array $config, CertificateResponse $response);
}
