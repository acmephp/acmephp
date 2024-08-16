<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli\Action;

use AcmePhp\Cli\Action\InstallAwsElbv2Action;
use AcmePhp\Cli\Aws\ClientFactory;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client;
use Aws\Iam\IamClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class InstallAwsElbv2ActionTest extends TestCase
{
    use ProphecyTrait;

    public function testHandle()
    {
        $domain = 'foo.bar';
        $region = 'eu-west-1';
        $loadBalancer = 'myElb';
        $config = array(
            'region' => $region,
            'loadbalancer' => $loadBalancer,
            'certificate_prefix' => 'foo_',
        );
        $response = new CertificateResponse(
            new CertificateRequest(
                new DistinguishedName($domain),
                new KeyPair(new PublicKey('publicPem'), new PrivateKey('privatePem')),
            ),
            new Certificate('certPem'),
        );

        $mockFactory = $this->prophesize(ClientFactory::class);
        $mockIam = $this->prophesize(IamClient::class);
        $mockElb = $this->prophesize(ElasticLoadBalancingV2Client::class);

        $action = new InstallAwsElbv2Action($mockFactory->reveal());

        $mockFactory->getIamClient($region)->willReturn($mockIam->reveal());
        $mockFactory->getElbv2Client($region)->willReturn($mockElb->reveal());
        $mockIam->uploadServerCertificate(Argument::any())->shouldBeCalled()->willReturn(array(
            'ServerCertificateMetadata' => array(
                'Arn' => 'certificate_arn',
            ),
        ));
        $mockIam->deleteServerCertificate(Argument::any())->shouldBeCalled();
        $mockIam->listServerCertificates(Argument::any())->willReturn(array(
            'ServerCertificateMetadataList' => array(
                array('ServerCertificateName' => 'foo_123'),
            ),
        ));
        $mockElb->describeLoadBalancers(array('Names' => array($loadBalancer)))->willReturn(array(
            'LoadBalancers' => array(
                array('LoadBalancerArn' => 'elb_arn'),
            ),
        ));
        $mockElb->describeListeners(array('LoadBalancerArn' => 'elb_arn'))->willReturn(array(
            'Listeners' => array(
                array('ListenerArn' => 'listener_arn1', 'Port' => 80),
                array('ListenerArn' => 'listener_arn2', 'Port' => 443),
            ),
        ));
        $mockElb->modifyListener(array(
            'Certificates' => array(
                array(
                    'CertificateArn' => 'certificate_arn',
                ),
            ),
            'ListenerArn' => 'listener_arn2',
        ))->shouldBeCalled();

        $action->handle($config, $response);
    }
}
