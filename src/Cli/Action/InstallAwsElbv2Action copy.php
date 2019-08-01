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

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\WafOpenapi\WafOpenapi;

/**
 * Action to install certificate in an AWS ELBv2.
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class InstallAliyunWafAction extends AbstractAction
{
    protected function installCertificate($certificateArn, $region, $loadBalancerName, $loadBalancerPort)
    {
        print_r($certificateArn);
        exit;

        AlibabaCloud::accessKeyClient('', '')->regionId('cn')->asDefaultClient();
        WafOpenapi::v20180117()->createCertAndKey()
            ->withCert($certificateArn)
            ->withKey()
            ->withDomain()
            ->withHttpsCertName()
            ->withInstanceId();
    }
}
