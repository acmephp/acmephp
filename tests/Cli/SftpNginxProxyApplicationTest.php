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

use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
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
        $sftpFilesystem = new Filesystem(new SftpAdapter([
            'host' => 'localhost',
            'port' => 8022,
            'username' => 'acmephp',
            'password' => 'acmephp',
            'root' => '/share',
        ]));

        // Remove any old version of the files
        $sftpFilesystem->has('private') && $sftpFilesystem->deleteDir('private');
        $sftpFilesystem->has('certs') && $sftpFilesystem->deleteDir('certs');
        $sftpFilesystem->has('nginxproxy') && $sftpFilesystem->deleteDir('nginxproxy');

        // Run the original full process
        parent::testFullProcess();

        // nginxproxy
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.key');

        // Backup
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/private/key.private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/private/key.public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/private/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/public/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/public/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/certs/acmephp.com/public/fullchain.pem');

        // Backup nginxproxy
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/backup/nginxproxy/acmephp.com.key');

        // SFTP
        $this->assertTrue($sftpFilesystem->has('account/key.private.pem'));
        $this->assertTrue($sftpFilesystem->has('account/key.public.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/private/key.private.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/private/key.public.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/private/combined.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/public/cert.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/public/chain.pem'));
        $this->assertTrue($sftpFilesystem->has('certs/acmephp.com/public/fullchain.pem'));
        $this->assertTrue($sftpFilesystem->has('nginxproxy/acmephp.com.crt'));
        $this->assertTrue($sftpFilesystem->has('nginxproxy/acmephp.com.key'));
    }
}
