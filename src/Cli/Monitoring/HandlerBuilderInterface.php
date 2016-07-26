<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Monitoring;

use Monolog\Handler\HandlerInterface;

interface HandlerBuilderInterface
{
    const LEVEL_ERROR = 'error';
    const LEVEL_INFO = 'info';

    /**
     * Create a handler usable with Monolog given a configuration.
     *
     * @param array $config
     *
     * @return HandlerInterface
     */
    public function createHandler($config);
}
