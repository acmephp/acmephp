<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli\Mock;

use AcmePhp\Cli\Application;
use Webmozart\PathUtil\Path;

class TestApplication extends Application
{
    /**
     * @return string
     */
    public function getStorageDirectory()
    {
        return Path::canonicalize(__DIR__.'/../Fixtures/local/master');
    }
}
