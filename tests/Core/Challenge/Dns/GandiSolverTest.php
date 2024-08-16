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
use AcmePhp\Core\Challenge\Dns\GandiSolver;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class GandiSolverTest extends TestCase
{
    use ProphecyTrait;

    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(ClientInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockClient->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $domain = 'sub-domain.bar.com';
        $recordName = '_acme-challenge.sub-domain.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(ClientInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockClient->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);

        $mockClient->request(
            'PUT',
            'https://dns.api.gandi.net/api/v5/domains/bar.com/records/_acme-challenge.sub-domain/TXT',
            array(
                'headers' => array(
                    'X-Api-Key' => 'stub',
                ),
                'json' => array(
                    'rrset_type' => 'TXT',
                    'rrset_ttl' => 600,
                    'rrset_name' => '_acme-challenge.sub-domain',
                    'rrset_values' => array('record_value'),
                ),
            ),
        )->shouldBeCalled();

        $solver->configure(array('api_key' => 'stub'));
        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $domain = 'sub-domain.bar.com';
        $recordName = '_acme-challenge.sub-domain.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(ClientInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockClient->reveal());

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);

        $mockClient->request(
            'DELETE',
            'https://dns.api.gandi.net/api/v5/domains/bar.com/records/_acme-challenge.sub-domain/TXT',
            array(
                'headers' => array(
                    'X-Api-Key' => 'stub',
                ),
            ),
        )->shouldBeCalled();

        $solver->configure(array('api_key' => 'stub'));
        $solver->cleanup($stubChallenge->reveal());
    }
}
