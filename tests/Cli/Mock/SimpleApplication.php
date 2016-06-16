<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli\Mock;

use Webmozart\PathUtil\Path;

class SimpleApplication extends AbstractTestApplication
{
    /**
     * @return string
     */
    public function getConfigFile()
    {
        return Path::canonicalize(__DIR__.'/../Fixtures/simple.conf');
    }
}
