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

namespace Tests\AcmePhp\Cli\Action;

use AcmePhp\Cli\Action\InstallAwsElbAction;
use AcmePhp\Cli\Aws\ClientFactory;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use Aws\ElasticLoadBalancing\ElasticLoadBalancingClient;
use Aws\Iam\IamClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class InstallAwsElbActionTest extends TestCase
{
    use ProphecyTrait;

    public function testHandle()
    {
        $domain = 'foo.bar';
        $region = 'eu-west-1';
        $loadBalancer = 'myElb';
        $config = [
            'region' => $region,
            'loadbalancer' => $loadBalancer,
            'certificate_prefix' => 'foo_',
        ];
        $response = new CertificateResponse(
            new CertificateRequest(
                new DistinguishedName($domain),
                new KeyPair(new PublicKey('publicPem'), new PrivateKey('privatePem')),
            ),
            new Certificate('certPem'),
        );

        $mockFactory = $this->prophesize(ClientFactory::class);
        $mockIam = $this->prophesize(IamClient::class);
        $mockElb = $this->prophesize(ElasticLoadBalancingClient::class);

        $action = new InstallAwsElbAction($mockFactory->reveal());

        $mockFactory->getIamClient($region)->willReturn($mockIam->reveal());
        $mockFactory->getElbClient($region)->willReturn($mockElb->reveal());
        $mockIam->uploadServerCertificate(Argument::any())->shouldBeCalled()->willReturn([
            'ServerCertificateMetadata' => [
                'Arn' => 'certificate_arn',
            ],
        ]);
        $mockIam->deleteServerCertificate(Argument::any())->shouldBeCalled();
        $mockIam->listServerCertificates(Argument::any())->willReturn([
            'ServerCertificateMetadataList' => [
                ['ServerCertificateName' => 'foo_123'],
            ],
        ]);
        $mockElb->setLoadBalancerListenerSSLCertificate([
            'LoadBalancerName' => $loadBalancer,
            'LoadBalancerPort' => 443,
            'SSLCertificateId' => 'certificate_arn',
        ])->shouldBeCalled();

        $action->handle($config, $response);
    }
}
