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

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use GuzzleHttp\Client;

class InstallCPanelAction extends AbstractAction
{
    /**
     * @var Client
     */
    private $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function handle(array $config, CertificateResponse $response)
    {
        $this->assertConfiguration($config, array('host', 'username', 'token'));

        $commonName = $response->getCertificateRequest()->getDistinguishedName()->getCommonName();
        $certificate = $response->getCertificate();
        $privateKey = $response->getCertificateRequest()->getKeyPair()->getPrivateKey();

        $issuerChain = array_map(function (Certificate $certificate) {
            return $certificate->getPEM();
        }, $certificate->getIssuerChain());

        $this->installCertificate(
            $config,
            $commonName,
            $certificate->getPEM(),
            implode("\n", $issuerChain),
            $privateKey->getPEM()
        );
    }

    private function installCertificate($config, $domain, $crt, $caBundle, $key)
    {
        $this->httpClient->request(
            'POST',
            $config['host'] . 'json-api/cpanel?' .
            'cpanel_jsonapi_apiversion=2&' .
            'cpanel_jsonapi_module=SSL&' .
            'cpanel_jsonapi_func=installssl&' .
            'domain=' . $domain . '&' .
            'crt=' . urlencode($crt) . '&' .
            'key=' . urlencode($key) . '&' .
            'cabundle=' . urlencode($caBundle),
            array('headers' => array('Authorization' => 'cpanel ' . $config['username'] . ':' . $config['token']),
            )
        );
    }
}
