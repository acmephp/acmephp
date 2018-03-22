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
use AcmePhp\Core\Challenge\Dns\Route53Solver;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Aws\Route53\Route53Client;
use Prophecy\Argument;

class Route53SolverTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(Route53Client::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new Route53Solver($mockExtractor->reveal(), $mockClient->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $domain = 'bar.com';
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(Route53Client::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new Route53Solver($mockExtractor->reveal(), $mockClient->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);
        $mockClient->listHostedZones([])->willReturn(
            [
                'HostedZones' => [
                    ['Id' => 1, 'Name' => 'foo.fr.'],
                    ['Id' => 2, 'Name' => 'bar.com.'],
                ],
                'NextMarker' => null,
                'IsTruncated' => false,
            ]
        );
        $mockClient->changeResourceRecordSets(Argument::any())->shouldBeCalled();

        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $domain = 'bar.com';
        $recordName = '_acme-challenge.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(Route53Client::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new Route53Solver($mockExtractor->reveal(), $mockClient->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);
        $mockClient->listHostedZones([])->willReturn(
            [
                'HostedZones' => [
                    ['Id' => 1, 'Name' => 'foo.fr.'],
                    ['Id' => 2, 'Name' => 'bar.com.'],
                ],
                'NextMarker' => null,
                'IsTruncated' => false,
            ]
        );
        $mockClient->listResourceRecordSets(Argument::any())->willReturn(
            [
                'ResourceRecordSets' => [
                    ['Id' => 1, 'Name' => $recordName, 'Type' => 'TXT'],
                ],
            ]
        );
        $mockClient->changeResourceRecordSets(Argument::any())->shouldBeCalled();

        $solver->cleanup($stubChallenge->reveal());
    }
}
