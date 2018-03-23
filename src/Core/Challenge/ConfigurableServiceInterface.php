<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge;

/**
 * ACME challenge solver.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface ConfigurableServiceInterface
{
    /**
     * Configure the service with a set of configuration.
     *
     * @param array $config
     */
    public function configure(array $config);
}
