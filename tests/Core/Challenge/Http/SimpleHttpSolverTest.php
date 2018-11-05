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

use AcmePhp\Core\Challenge\Http\HttpDataExtractor;
use AcmePhp\Core\Challenge\Http\SimpleHttpSolver;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class SimpleHttpSolverTest extends TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleHttpSolver();

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $checkUrl = 'http://foo.bar/.challenge';
        $checkContent = 'randomPayload';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockOutput = $this->prophesize(OutputInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleHttpSolver($mockExtractor->reveal(), $mockOutput->reveal());

        $mockExtractor->getCheckUrl($stubChallenge->reveal())->willReturn($checkUrl);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockOutput->writeln(Argument::any())->shouldBeCalled();

        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $checkUrl = 'http://foo.bar/.challenge';
        $checkContent = 'randomPayload';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockOutput = $this->prophesize(OutputInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleHttpSolver($mockExtractor->reveal(), $mockOutput->reveal());

        $mockExtractor->getCheckUrl($stubChallenge->reveal())->willReturn($checkUrl);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockOutput->writeln(Argument::any())->shouldBeCalled();

        $solver->cleanup($stubChallenge->reveal());
    }
}
