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

use Webmozart\Assert\Assert;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractAction implements ActionInterface
{
    /**
     * @param array $configuration
     * @param array $keys
     */
    protected function assertConfiguration($configuration, $keys)
    {
        foreach ($keys as $key) {
            Assert::keyExists(
                $configuration,
                $key,
                'Configuration key "%s" is required for this action'
            );
        }
    }
}
