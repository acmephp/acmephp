<?php

namespace AcmePhp\Cli\Aws;

use Aws\ElasticLoadBalancing\ElasticLoadBalancingClient;
use Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client;
use Aws\Iam\IamClient;

class ClientFactory
{
    public function getIamClient($region = null)
    {
        return new IamClient($this->getClientArgs(['region' => $region, 'version' => '2010-05-08']));
    }

    public function getElbClient($region = null)
    {
        return new ElasticLoadBalancingClient(
            $this->getClientArgs(['region' => $region, 'version' => '2012-06-01'])
        );
    }

    public function getElbv2Client($region = null)
    {
        return new ElasticLoadBalancingV2Client(
            $this->getClientArgs(['region' => $region, 'version' => '2015-12-01'])
        );
    }

    private function getClientArgs(array $args = [])
    {
        if (empty($args['region'])) {
            $args['region'] = getenv('AWS_DEFAULT_REGION');
        }

        return $args;
    }
}
