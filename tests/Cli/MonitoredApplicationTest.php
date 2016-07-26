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

use AcmePhp\Cli\Command\AbstractCommand;
use AcmePhp\Cli\Monitoring\HandlerBuilderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\AcmePhp\Cli\Mock\AbstractTestApplication;
use Tests\AcmePhp\Cli\Mock\MonitoredApplication;

class MonitoredApplicationTest extends AbstractApplicationTest
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
        ];
    }

    /**
     * @return AbstractTestApplication
     */
    protected function createApplication()
    {
        return new MonitoredApplication();
    }

    public function testFullProcess()
    {
        parent::testFullProcess();

        /*
         * Renewal without issue
         */
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
            '--email'        => 'galopintitouan@gmail.com',
            '--force'        => true,
        ]);

        $requestDisplay = $commandTester->getDisplay();

        $this->assertContains('Certificate renewed successfully!', $requestDisplay);
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/fullchain.pem');
    }

    public function testRenewalWithIssue()
    {
        parent::testFullProcess();

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
            '--email'        => 'galopintitouan@gmail.com',
            '--force'        => true,
        ]);

        $requestDisplay = $commandTester->getDisplay();

        $this->assertContains('Certificate renewed successfully!', $requestDisplay);
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/private.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/private/acmephp.com/public.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/cert.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/combined.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/chain.pem');
        $this->assertFileExists(__DIR__.'/../Cli/Fixtures/local/master/certs/acmephp.com/fullchain.pem');

        /*
         * Mock monitoring handlers
         */

        // Initialize container
        $parentReflection = new \ReflectionClass(AbstractCommand::class);
        $containerReflection = $parentReflection->getProperty('container');
        $containerReflection->setAccessible(true);
        $containerReflection->setValue($command, null);

        $commandReflection = new \ReflectionObject($command);
        $initializer = $commandReflection->getMethod('initializeContainer');
        $initializer->setAccessible(true);
        $initializer->invoke($command);

        // Replace handlers builders by mocks
        $handler = new TestHandler();

        $handlerBuilder = $this->getMock(HandlerBuilderInterface::class);
        $handlerBuilder
            ->expects($this->exactly(2))
            ->method('createHandler')
            ->willReturn($handler);

        /** @var ContainerInterface $container */
        $container = $containerReflection->getValue($command);
        $container->set('monitoring.email', $handlerBuilder);
        $container->set('monitoring.slack', $handlerBuilder);

        // Introduce HTTP issue
        $container->set('http.raw_client', new Client(['handler' => new MockHandler([
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ])]));

        // Set new container
        $containerReflection->setValue($command, $container);

        /*
         * Renewal with issue
         */
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
            '--email'        => 'galopintitouan@gmail.com',
            '--force'        => true,
        ]);

        $requestDisplay = $commandTester->getDisplay();
        $this->assertNotContains('Certificate renewed successfully!', $requestDisplay);

        $records = $handler->getRecords();

        $this->assertCount(2, $records);
        $this->assertSame(Logger::ALERT, $records[0]['level']);
        $this->assertSame('A critical error occured during certificate renewal', $records[0]['message']);
        $this->assertSame(Logger::ALERT, $records[1]['level']);
        $this->assertSame('A critical error occured during certificate renewal', $records[1]['message']);
    }
}
