<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli;

class SimpleApplicationTest extends AbstractApplicationTest
{
    protected function getFixturesDirectories(): array
    {
        return array(
            __DIR__ . '/../Cli/Fixtures/local/master',
        );
    }

    protected function getConfigDir(): string
    {
        return __DIR__ . '/Fixtures/config/simple';
    }
}
