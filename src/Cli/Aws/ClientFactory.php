<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Aws;

use Aws\ElasticLoadBalancing\ElasticLoadBalancingClient;
use Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client;
use Aws\Iam\IamClient;

class ClientFactory
{
    public function getIamClient($region = null): IamClient
    {
        return new IamClient($this->getClientArgs(array('region' => $region, 'version' => '2010-05-08')));
    }

    public function getElbClient($region = null): ElasticLoadBalancingClient
    {
        return new ElasticLoadBalancingClient(
            $this->getClientArgs(array('region' => $region, 'version' => '2012-06-01'))
        );
    }

    public function getElbv2Client($region = null): ElasticLoadBalancingV2Client
    {
        return new ElasticLoadBalancingV2Client(
            $this->getClientArgs(array('region' => $region, 'version' => '2015-12-01'))
        );
    }

    private function getClientArgs(array $args = array())
    {
        if (empty($args['region'])) {
            $args['region'] = getenv('AWS_DEFAULT_REGION');
        }

        return $args;
    }
}
