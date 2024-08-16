<?php

declare(strict_types=1);

/*
 * This file is part of the Acme PHP project.
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
use Tests\AcmePhp\Cli\Mock\TestApplication;
use Tests\AcmePhp\Core\AbstractFunctionnalTest;

abstract class AbstractApplicationTest extends AbstractFunctionnalTest
{
    /**
     * @var TestApplication
     */
    protected $application;

    abstract protected function getFixturesDirectories(): array;

    abstract protected function getConfigDir(): string;

    public function setUp(): void
    {
        $this->cleanContext();

        $this->application = new TestApplication();
    }

    public function tearDown(): void
    {
        $this->cleanContext();
    }

    public function testFullProcess()
    {
        $runTester = new CommandTester($this->application->find('run'));
        $runTester->execute([
            'command' => 'run',
            'config' => $this->getConfigDir() . '/' . ('eab' === getenv('PEBBLE_MODE') ? 'eab' : 'default') . '.yaml',
        ]);

        $output = $runTester->getDisplay();

        // Register
        $this->assertStringContainsString('No account key pair was found, generating one', $output);
        $this->assertStringContainsString('Account registered successfully', $output);
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/account/key.private.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/account/key.public.pem');

        // Challenge
        $this->assertStringContainsString('Requesting certificate order', $output);
        $this->assertStringContainsString('Solving challenge for domain acmephp.com', $output);
        $this->assertStringContainsString('Requesting authorization check for domain acmephp.com', $output);
        $this->assertStringContainsString('Cleaning up challenge for domain acmephp.com', $output);

        // Certificate
        $this->assertStringContainsString('Requesting certificate for domain acmephp.com', $output);
        $this->assertStringContainsString('Certificate requested successfully!', $output);
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/private/key.private.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/private/key.public.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/public/cert.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/private/combined.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/public/chain.pem');
        $this->assertFileExists(__DIR__ . '/../Cli/Fixtures/local/master/certs/acmephp.com/public/fullchain.pem');
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
                ->getIterator(),
        );
    }
}
