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

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\AcmePhp\Cli\Mock\AbstractTestApplication;
use Tests\AcmePhp\Cli\Mock\SimpleApplication;
use Webmozart\PathUtil\Path;

abstract class AbstractApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractTestApplication
     */
    protected $application;

    /**
     * @return array
     */
    abstract protected function getFixturesDirectories();

    /**
     * @return AbstractTestApplication
     */
    abstract protected function createApplication();

    public function setUp()
    {
        $this->cleanContext();

        $this->application = $this->createApplication();
    }

    public function tearDown()
    {
        $this->cleanContext();
    }

    public function testFullProcess()
    {
        /*
         * Register
         */
        $register = $this->application->find('register');
        $registerTester = new CommandTester($register);
        $registerTester->execute([
            'command'     => $register->getName(),
            'email'       => 'foo@example.com',
            '--server'    => 'http://127.0.0.1:4000/directory',
            '--agreement' => 'http://boulder:4000/terms/v1',
        ]);

        $registerDisplay = $registerTester->getDisplay();

        $this->assertContains('No account key pair was found, generating one', $registerDisplay);
        $this->assertContains('Account registered successfully', $registerDisplay);
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/_account/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/_account/public.pem');

        /*
         * Authorize
         */
        $authorize = $this->application->find('authorize');
        $authorizeTest = new CommandTester($authorize);
        $authorizeTest->execute([
            'command'  => $authorize->getName(),
            'domain'   => 'acmephp.com',
            '--server' => 'http://127.0.0.1:4000/directory',
        ]);

        $authorizeDisplay = $authorizeTest->getDisplay();

        $this->assertContains('The authorization token was successfully fetched', $authorizeDisplay);
        $this->assertContains('http://acmephp.com/.well-known/acme-challenge/', $authorizeDisplay);
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/authorization_challenge.json');

        /*
         * Check
         */

        // Find challenge and expose token
        $challenge = json_decode(
            file_get_contents(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/authorization_challenge.json'),
            true
        );

        $process = $this->createServerProcess($challenge['token'], $challenge['payload']);
        $process->start();

        $this->assertTrue($process->isRunning());

        try {
            $check = $this->application->find('check');
            $checkTest = new CommandTester($check);
            $checkTest->execute([
                'command'   => $check->getName(),
                'domain'    => 'acmephp.com',
                '--server'  => 'http://127.0.0.1:4000/directory',
                '--no-test' => null,
            ]);

            $checkDisplay = $checkTest->getDisplay();

            $this->assertContains('The authorization check was successful', $checkDisplay);
        } finally {
            $process->stop();
        }

        /*
         * Request
         */
        $request = $this->application->find('request');
        $requestTest = new CommandTester($request);
        $requestTest->execute([
            'command'        => $request->getName(),
            'domain'         => 'acmephp.com',
            '--server'       => 'http://127.0.0.1:4000/directory',
            '--country'      => 'FR',
            '--province'     => 'Ile de France',
            '--locality'     => 'Paris',
            '--organization' => 'Acme PHP',
            '--unit'         => 'Sales',
            '--email'        => 'galopintitouan@gmail.com',
        ]);

        $requestDisplay = $requestTest->getDisplay();

        $this->assertContains('The SSL certificate was fetched successfully', $requestDisplay);
        $this->assertContains(Path::canonicalize(__DIR__.'/Fixtures/local/master'), $requestDisplay);
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/fullchain.pem');
    }

    /**
     * @expectedException \AcmePhp\Cli\Exception\AcmeCliException
     * @expectedExceptionMessage Loading of account key pair failed
     */
    public function testAuthorizeWithoutKeyFail()
    {
        $this->application = new SimpleApplication();

        $command = $this->application->find('authorize');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'domain'   => 'example.com',
            '--server' => 'http://127.0.0.1:4000/directory',
        ]);
    }

    /**
     * @expectedException \AcmePhp\Cli\Exception\AcmeCliException
     */
    public function testCheckWithoutKeyFail()
    {
        $this->application = new SimpleApplication();

        $command = $this->application->find('check');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'domain'   => 'example.com',
            '--server' => 'http://127.0.0.1:4000/directory',
        ]);
    }

    /**
     * @expectedException \AcmePhp\Cli\Exception\AcmeCliException
     */
    public function testRequestWithoutKeyFail()
    {
        $this->application = new SimpleApplication();

        $command = $this->application->find('request');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'        => $command->getName(),
            'domain'         => 'acmephp.com',
            '--server'       => 'http://127.0.0.1:4000/directory',
            '--country'      => 'FR',
            '--province'     => 'Ile de France',
            '--locality'     => 'Paris',
            '--organization' => 'Acme PHP',
            '--unit'         => 'Sales',
            '--email'        => 'meClientTesgalopintitouan@gmail.com',
        ]);
    }

    /**
     * @param string $token
     * @param string $payload
     *
     * @return Process
     */
    private function createServerProcess($token, $payload)
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

        $script = implode(' ', array_map(['Symfony\Component\Process\ProcessUtils', 'escapeArgument'], [
            $binary,
            '-S',
            $listen,
            '-t',
            $documentRoot,
        ]));

        return new Process('exec '.$script, $documentRoot, null, null, null);
    }

    /**
     * Remove fixtures files and directories to have a clean context.
     */
    private function cleanContext()
    {
        $filesystem = new Filesystem();
        $finder = new Finder();

        $filesystem->remove(
            $finder
                ->in($this->getFixturesDirectories())
                ->notName('.gitkeep')
                ->getIterator()
        );
    }
}
