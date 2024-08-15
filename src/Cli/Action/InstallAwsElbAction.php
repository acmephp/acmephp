<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Action;

/**
 * Action to install certificate in an AWS ELB.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class InstallAwsElbAction extends AbstractAwsAction
{
    protected function installCertificate($certificateArn, $region, $loadBalancerName, $loadBalancerPort)
    {
        $elbClient = $this->clientFactory->getElbClient($region);
        $this->retryCall(
            function () use ($elbClient, $loadBalancerName, $loadBalancerPort, $certificateArn) {
                $elbClient->setLoadBalancerListenerSSLCertificate(
                    array(
                        'LoadBalancerName' => $loadBalancerName,
                        'LoadBalancerPort' => $loadBalancerPort,
                        'SSLCertificateId' => $certificateArn,
                    )
                );
            },
            30
        );
    }
}
