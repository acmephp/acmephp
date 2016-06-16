<?php

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
     * Return this action name.
     */
    public function getName();

    /**
     * Get a certificate response and execute the action with it.
     * Use the given configuration if needed.
     *
     * @param array               $config
     * @param CertificateResponse $response
     */
    public function handle($config, CertificateResponse $response);
}
