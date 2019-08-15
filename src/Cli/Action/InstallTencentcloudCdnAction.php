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
use Exception;
use function GuzzleHttp\json_decode;

/**
 * Action to install certificate in an Aliyun Waf.
 *
 * @author Xiaohui Lam <xiaohui.lam@aliyun.com>
 */
class InstallTencentcloudCdnAction extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function handle($config, CertificateResponse $response)
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

        $data = [
            'host' => $config['host'],
            'httpsType' => $config['https_type'],
            'cert' => $cert,
            'privateKey' => $key,
        ];

        $response = $this->_createRequest($config['secret_id'], $config['secret_key'], $data, true);
        if (!isset($response['code'])) {
            throw new Exception('Install fail!', 1);
        }
        if ($response['code'] != 0) {
            throw new Exception((isset($response['codeDesc']) ? $response['codeDesc'] : '') . (isset($response['message']) ? $response['message'] : ''), $response['code']);
        }
    }

    protected function _createRequest($secretId, $secretKey, $privateParams, $isHttps)
    {
        $HttpMethod = 'POST';

        $commonParams = [
            'Nonce' => rand(),
            'Timestamp' => time(NULL),
            'Action' => 'SetHttpsInfo',
            'SecretId' => $secretId,
        ];

        $HttpUrl = 'cdn.api.qcloud.com';
        $FullHttpUrl = $HttpUrl . "/v2/index.php";
        $ReqParaArray = array_merge($commonParams, $privateParams);
        ksort($ReqParaArray);

        $SigTxt = $HttpMethod . $FullHttpUrl . "?";
        $isFirst = true;
        foreach ($ReqParaArray as $key => $value) {
            if (!$isFirst) {
                $SigTxt = $SigTxt . "&";
            }
            $isFirst = false;
            if (strpos($key, '_')) {
                $key = str_replace('_', '.', $key);
            }
            $SigTxt = $SigTxt . $key . "=" . $value;
        }

        $Signature = base64_encode(hash_hmac('sha1', $SigTxt, $secretKey, true));

        $Req = "Signature=" . urlencode($Signature);
        foreach ($ReqParaArray as $key => $value) {
            $Req = $Req . "&" . $key . "=" . urlencode($value);
        }

        if ($HttpMethod === 'GET') {
            if ($isHttps) {
                $Req = "https://" . $FullHttpUrl . "?" . $Req;
            } else {
                $Req = "http://" . $FullHttpUrl . "?" . $Req;
            }
            $Rsp = file_get_contents($Req);
        } else {
            if ($isHttps) {
                $Rsp = $this->_sendPost("https://" . $FullHttpUrl, $Req);
            } else {
                $Rsp = $this->_sendPost("http://" . $FullHttpUrl, $Req);
            }
        }

        return json_decode($Rsp, true);
    }

    protected function _sendPost($FullHttpUrl, $Req)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $Req);
        curl_setopt($ch, CURLOPT_URL, $FullHttpUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        return $result;
    }
}
