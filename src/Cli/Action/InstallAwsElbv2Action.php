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
 * Action to install certificate in an AWS ELBv2.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class InstallAwsElbv2Action extends AbstractAwsAction
{
    protected function installCertificate($certificateArn, $region, $loadBalancerName, $loadBalancerPort)
    {
        $elbv2Client = $this->clientFactory->getElbv2Client($region);
        $elb = $elbv2Client->describeLoadBalancers(['Names' => [$loadBalancerName]]);
        if (1 !== \count($elb['LoadBalancers'])) {
            throw new \Exception(sprintf('Unable to find Load balancer "%s"', $loadBalancerName));
        }
        $loadBalancerArn = $elb['LoadBalancers'][0]['LoadBalancerArn'];
        $listeners = $elbv2Client->describeListeners([
            'LoadBalancerArn' => $loadBalancerArn,
        ]);

        $listenerArn = null;
        foreach ($listeners['Listeners'] as $listener) {
            if ($listener['Port'] === $loadBalancerPort) {
                $listenerArn = $listener['ListenerArn'];
                break;
            }
        }

        if (null === $listenerArn) {
            throw new \Exception(sprintf('Unable to find a listener for %s on %d', $loadBalancerName, $loadBalancerPort));
        }

        $this->retryCall(
            function () use ($elbv2Client, $listenerArn, $certificateArn): void {
                $elbv2Client->modifyListener([
                    'Certificates' => [
                        [
                            'CertificateArn' => $certificateArn,
                        ],
                    ],
                    'ListenerArn' => $listenerArn,
                ]);
            },
            30
        );
    }
}
