<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\Dns\DnsDataExtractor;
use AcmePhp\Core\Challenge\Dns\SimpleDnsSolver;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class SimpleDnsSolverTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockOutput = $this->prophesize(OutputInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleDnsSolver($mockExtractor->reveal(), $mockOutput->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockOutput = $this->prophesize(OutputInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleDnsSolver($mockExtractor->reveal(), $mockOutput->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);

        $mockOutput->writeln(Argument::any())->shouldBeCalled();

        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockOutput = $this->prophesize(OutputInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new SimpleDnsSolver($mockExtractor->reveal(), $mockOutput->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);

        $mockOutput->writeln(Argument::any())->shouldBeCalled();

        $solver->cleanup($stubChallenge->reveal());
    }
}
