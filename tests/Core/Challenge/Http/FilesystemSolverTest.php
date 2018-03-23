<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\Http\FilesystemSolver;
use AcmePhp\Core\Challenge\Http\HttpDataExtractor;
use AcmePhp\Core\Filesystem\FilesystemFactoryInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use League\Flysystem\FilesystemInterface;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;

class FilesystemSolverTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new FilesystemSolver();

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $checkPath = '/.challenge';
        $checkContent = 'randomPayload';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockLocator = $this->prophesize(ContainerInterface::class);
        $mockFlysystemFactory = $this->prophesize(FilesystemFactoryInterface::class);
        $mockFlysystem = $this->prophesize(FilesystemInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new FilesystemSolver($mockLocator->reveal(), $mockExtractor->reveal());

        $mockLocator->get('stub')->willReturn($mockFlysystemFactory->reveal());
        $mockFlysystemFactory->create(Argument::any())->willReturn($mockFlysystem->reveal());
        $mockExtractor->getCheckPath($stubChallenge->reveal())->willReturn($checkPath);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockFlysystem->write($checkPath, $checkContent)->shouldBeCalled();

        $solver->configure(['adapter' => 'stub']);
        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $checkPath = '/.challenge';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockLocator = $this->prophesize(ContainerInterface::class);
        $mockFlysystemFactory = $this->prophesize(FilesystemFactoryInterface::class);
        $mockFlysystem = $this->prophesize(FilesystemInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new FilesystemSolver($mockLocator->reveal(), $mockExtractor->reveal());

        $mockLocator->get('stub')->willReturn($mockFlysystemFactory->reveal());
        $mockFlysystemFactory->create(Argument::any())->willReturn($mockFlysystem->reveal());
        $mockExtractor->getCheckPath($stubChallenge->reveal())->willReturn($checkPath);

        $mockFlysystem->delete($checkPath)->shouldBeCalled();

        $solver->configure(['adapter' => 'stub']);
        $solver->cleanup($stubChallenge->reveal());
    }
}
