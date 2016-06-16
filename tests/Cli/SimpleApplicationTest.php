<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli;

use Tests\AcmePhp\Cli\Mock\AbstractTestApplication;
use Tests\AcmePhp\Cli\Mock\SimpleApplication;

class SimpleApplicationTest extends AbstractApplicationTest
{
    /**
     * @return array
     */
    protected function getFixturesDirectories()
    {
        return [
            __DIR__.'/../Cli/Fixtures/challenges/.well-known/acme-challenge',
            __DIR__.'/../Cli/Fixtures/local/master',
        ];
    }

    /**
     * @return AbstractTestApplication
     */
    protected function createApplication()
    {
        return new SimpleApplication();
    }
}
