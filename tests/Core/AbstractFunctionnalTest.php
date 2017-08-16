<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

abstract class AbstractFunctionnalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $token
     * @param string $payload
     *
     * @return Process
     */
    protected function createServerProcess($token, $payload)
    {
        $listen = '0.0.0.0:5002';
        $documentRoot = __DIR__.'/Fixtures/challenges';

        // Create file
        file_put_contents($documentRoot.'/.well-known/acme-challenge/'.$token, $payload);

        // Start server
        $finder = new PhpExecutableFinder();

        if (false === $binary = $finder->find()) {
            throw new \RuntimeException('Unable to find PHP binary to start server.');
        }

        $script = implode(' ', [
            '"'.$binary.'"',
            '"-S"',
            '"'.$listen.'"',
            '"-t"',
            '"'.$documentRoot.'"',
        ]);

        return new Process('exec '.$script, $documentRoot, null, null, null);
    }
}
