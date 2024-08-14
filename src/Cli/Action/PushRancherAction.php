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
use GuzzleHttp\Psr7\Uri;

use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use function GuzzleHttp\Psr7\copy_to_string;

/**
 * Action to upload SSL certificates to Rancher using its API.
 *
 * @see http://docs.rancher.com/rancher/v1.2/en/api/api-resources/certificate/
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PushRancherAction implements ActionInterface
{
    public function __construct(
        private readonly Client $httpClient,
    ) {
    }

    public function handle(array $config, CertificateResponse $response): void
    {
        $payload = $this->createRancherPayloadFromResponse($response);

        $commonName = $response->getCertificateRequest()->getDistinguishedName()->getCommonName();
        $currentCertificates = $this->getRancherCertificates($config);

        $updated = false;

        foreach ($currentCertificates as $certificate) {
            if ($certificate['name'] === $commonName) {
                $updated = true;
                $this->updateRancherCertificate($config, $certificate['id'], $payload);
            }
        }

        if (!$updated) {
            $this->createRancherCertificate($config, $payload);
        }
    }

    private function createRancherPayloadFromResponse(CertificateResponse $response): string
    {
        $certificate = $response->getCertificate();
        $privateKey = $response->getCertificateRequest()->getKeyPair()->getPrivateKey();

        $issuerChain = array_map(fn (Certificate $certificate): string => $certificate->getPEM(), $certificate->getIssuerChain());

        return json_encode([
            'name' => $response->getCertificateRequest()->getDistinguishedName()->getCommonName(),
            'description' => 'Generated with Acme PHP',
            'cert' => $certificate->getPEM(),
            'certChain' => implode("\n", $issuerChain),
            'key' => $privateKey->getPEM(),
        ]);
    }

    /**
     * @return mixed[]
     */
    private function getRancherCertificates(array $config): array
    {
        $nextPage = $this->createUrl($config, '/v1/certificates');
        $certificates = [];

        while ($nextPage) {
            $page = $this->request('GET', $nextPage);
            $certificates = array_merge($certificates, $page['data']);

            $nextPage = null;
            if (isset($page['pagination'], $page['pagination']['next']) && \is_string($page['pagination']['next'])) {
                $nextPage = $page['pagination']['next'];
            }
        }

        return $certificates;
    }

    private function updateRancherCertificate(array $config, string $previousCertificateId, string $newPayload): void
    {
        $this->request('PUT', $this->createUrl($config, '/v1/certificates/'.$previousCertificateId), $newPayload);
    }

    private function createRancherCertificate(array $config, string $payload): void
    {
        $this->request('POST', $this->createUrl($config, '/v1/certificates'), $payload);
    }

    private function createUrl(array $config, string $endpoint): string
    {
        $url = (new Uri())
            ->withScheme($config['ssl'] ? 'https' : 'http')
            ->withUserInfo($config['access_key'], $config['secret_key'])
            ->withHost($config['host'])
            ->withPort($config['port'])
            ->withPath($endpoint);

        return (string) $url;
    }

    private function request(string $method, string $url, $body = null)
    {
        $response = $this->httpClient->request($method, $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $body ?: '',
        ]);

        return json_decode(copy_to_string($response->getBody()), true);
    }
}
