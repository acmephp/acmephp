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
    public function __construct(
        private readonly Client $httpClient
    ) {
    }

    public function handle(array $config, CertificateResponse $response): void
    {
        $this->assertConfiguration($config, ['host', 'username', 'token']);

        $commonName = $response->getCertificateRequest()->getDistinguishedName()->getCommonName();
        $certificate = $response->getCertificate();
        $privateKey = $response->getCertificateRequest()->getKeyPair()->getPrivateKey();

        $issuerChain = array_map(fn (Certificate $certificate): string => $certificate->getPEM(), $certificate->getIssuerChain());

        $this->installCertificate(
            $config,
            $commonName,
            $certificate->getPEM(),
            implode("\n", $issuerChain),
            $privateKey->getPEM()
        );
    }

    private function installCertificate(array $config, string $domain, string $crt, string $caBundle, string $key): void
    {
        $this->httpClient->request('POST', $config['host'].'json-api/cpanel?'.
            'cpanel_jsonapi_apiversion=2&'.
            'cpanel_jsonapi_module=SSL&'.
            'cpanel_jsonapi_func=installssl&'.
            'domain='.$domain.'&'.
            'crt='.urlencode((string) $crt).'&'.
            'key='.urlencode((string) $key).'&'.
            'cabundle='.urlencode((string) $caBundle),
            ['headers' => ['Authorization' => 'cpanel '.$config['username'].':'.$config['token']],
            ]);
    }
}
