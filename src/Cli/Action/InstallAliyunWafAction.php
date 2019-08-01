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
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Client\Exception\ClientException;

/**
 * Action to install certificate in an Aliyun Waf.
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

        try {
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
        } catch (ServerException $e) {
            throw $e;
        } catch (ClientException $e) {
            throw $e;
        }
    }
}
