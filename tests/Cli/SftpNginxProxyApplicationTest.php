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
use Tests\AcmePhp\Cli\Mock\SftpNginxProxyApplication;

class SftpNginxProxyApplicationTest extends AbstractApplicationTest
{
    /**
     * @return array
     */
    protected function getFixturesDirectories()
    {
        return [
            __DIR__.'/../Cli/Fixtures/challenges/.well-known/acme-challenge',
            __DIR__.'/../Cli/Fixtures/local/backup',
            __DIR__.'/../Cli/Fixtures/local/master',
            __DIR__.'/../Cli/Fixtures/sftp',
        ];
    }

    /**
     * @return AbstractTestApplication
     */
    protected function createApplication()
    {
        return new SftpNginxProxyApplication();
    }

    public function testFullProcess()
    {
        parent::testFullProcess();

        // nginxproxy
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.key');

        // Backup
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/private/acmephp.com/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/private/acmephp.com/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/fullchain.pem');

        // Backup nginxproxy
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/nginxproxy/acmephp.com.key');

        // SFTP
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/private/_account/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/private/_account/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/private/acmephp.com/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/private/acmephp.com/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/certs/acmephp.com/fullchain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/certs/acmephp.com/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/certs/acmephp.com/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/certs/acmephp.com/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/sftp/nginxproxy/acmephp.com.key');
    }
}
