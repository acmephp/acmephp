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
use AcmePhp\Core\Filesystem\FilesystemInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class FilesystemSolverTest extends TestCase
{
    use ProphecyTrait;

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

        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockExtractor->getCheckPath($stubChallenge->reveal())->willReturn($checkPath);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockFlysystem = $this->prophesize(FilesystemInterface::class);
        $mockFlysystem->write($checkPath, $checkContent)->shouldBeCalled();

        $mockFlysystemFactory = $this->prophesize(FilesystemFactoryInterface::class);
        $mockFlysystemFactory->create(Argument::any())->willReturn($mockFlysystem->reveal());

        $mockLocator = $this->prophesize(ContainerInterface::class);
        $mockLocator->get('stub')->willReturn($mockFlysystemFactory->reveal());

        $solver = new FilesystemSolver($mockLocator->reveal(), $mockExtractor->reveal());

        $solver->configure(['adapter' => 'stub']);
        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $checkPath = '/.challenge';

        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockExtractor->getCheckPath($stubChallenge->reveal())->willReturn($checkPath);

        $mockFlysystem = $this->prophesize(FilesystemInterface::class);

        $mockFlysystemFactory = $this->prophesize(FilesystemFactoryInterface::class);
        $mockFlysystemFactory->create(Argument::any())->willReturn($mockFlysystem->reveal());

        $mockLocator = $this->prophesize(ContainerInterface::class);
        $mockLocator->get('stub')->willReturn($mockFlysystemFactory->reveal());

        $solver = new FilesystemSolver($mockLocator->reveal(), $mockExtractor->reveal());

        $mockFlysystem->delete($checkPath)->shouldBeCalled();

        $solver->configure(['adapter' => 'stub']);
        $solver->cleanup($stubChallenge->reveal());
    }
}
