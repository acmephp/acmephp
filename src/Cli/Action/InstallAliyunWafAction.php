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
use AcmePhp\Ssl\CertificateResponse;

/**
 * Action to install certificate in an AWS ELBv2.
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class InstallAliyunWafAction extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function handle($config, CertificateResponse $response)
    {
        $issuerChain = [];
        $issuerCertificate = $response->getCertificate()->getIssuerCertificate();
        while (null !== $issuerCertificate) {
            $issuerChain[] = $issuerCertificate->getPEM();
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }
        $cert = implode("\n", $issuerChain);

        $key = $response->getCertificateRequest()->getKeyPair()->getPrivateKey()->getPEM();

        AlibabaCloud::accessKeyClient('', '')->regionId('cn')->asDefaultClient();
        WafOpenapi::v20180117()->createCertAndKey()
            ->withCert($cert)
            ->withKey($key)
            ->withDomain()
            ->withHttpsCertName()
            ->withInstanceId();
    }
}
