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
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

class SftpNginxProxyApplicationTest extends AbstractApplicationTest
{
    protected function getFixturesDirectories(): array
    {
        return [
            __DIR__.'/../Cli/Fixtures/local/backup',
            __DIR__.'/../Cli/Fixtures/local/master',
            __DIR__.'/../Cli/Fixtures/sftp',
        ];
    }

    protected function getConfigDir(): string
    {
        return __DIR__.'/Fixtures/config/sfpt_nginxproxy';
    }

    public function testFullProcess()
    {
        $sftpFilesystem = new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                host: 'localhost',
                port: 8022,
                username: 'acmephp',
                password: 'acmephp',
            ),
            '/share',
        ));

        // Remove any old version of the files
        $sftpFilesystem->has('private') && $sftpFilesystem->deleteDirectory('private');
        $sftpFilesystem->has('certs') && $sftpFilesystem->deleteDirectory('certs');
        $sftpFilesystem->has('nginxproxy') && $sftpFilesystem->deleteDirectory('nginxproxy');

        // Run the original full process
        parent::testFullProcess();

        // nginxproxy
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.crt');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/nginxproxy/acmephp.com.key');

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
