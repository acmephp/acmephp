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
use AlibabaCloud\Cdn\Cdn;
use AlibabaCloud\Client\AlibabaCloud;

/**
 * Action to install certificate in an Aliyun Waf.
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class InstallAliyunCdnAction extends AbstractAction
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
        Cdn::v20180510()->setDomainServerCertificate()
            ->withDomainName($config['domain'])
            ->withCertName($config['domain'] . '_' . date('Y_m_d_H_i_s'))
            ->withCertType('upload')
            ->withForceSet(1)
            ->withServerCertificate($cert)
            ->withPrivateKey($key)
            ->withServerCertificateStatus('on')
            ->request();
    }
}
