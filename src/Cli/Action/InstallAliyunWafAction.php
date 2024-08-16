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

use AcmePhp\Ssl\CertificateResponse;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\WafOpenapi\WafOpenapi;

/**
 * Action to install certificate in an Aliyun Waf.
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class InstallAliyunWafAction extends AbstractAction
{
    public function handle(array $config, CertificateResponse $response)
    {
        $issuerChain = [];
        $issuerChain[] = $response->getCertificate()->getPEM();

        $issuerCertificate = $response->getCertificate()->getIssuerCertificate();
        while (null !== $issuerCertificate) {
            $issuerChain[] = $issuerCertificate->getPEM();
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }
        $cert = implode("\n", $issuerChain);

        $key = $response->getCertificateRequest()->getKeyPair()->getPrivateKey()->getPEM();

        AlibabaCloud::accessKeyClient($config['accessKeyId'], $config['accessKeySecret'])->regionId('cn-hangzhou')->asDefaultClient();
        $response = WafOpenapi::v20161111()->upgradeInstance()
            ->host('wafopenapi.cn-hangzhou.aliyuncs.com')
            ->action('CreateCertAndKey')
            ->setProtocol('https')
            ->version('2018-01-17')
            ->withCert($cert)
            ->withKey($key)
            ->withDomain($config['domain'])
            ->withHttpsCertName($config['domain'])
            ->withInstanceId($config['instanceId'])
            ->request();
    }
}
