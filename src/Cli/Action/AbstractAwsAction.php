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

use AcmePhp\Cli\Aws\ClientFactory;
use AcmePhp\Ssl\CertificateResponse;
use Aws\Iam\Exception\IamException;

/**
 * Action to install certificate in an AWS ELB.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
abstract class AbstractAwsAction extends AbstractAction
{
    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $config, CertificateResponse $response)
    {
        $this->assertConfiguration($config, ['loadbalancer', 'region']);

        $region = $config['region'];
        $loadBalancerName = $config['loadbalancer'];
        $loadBalancerPort = empty($config['listener']) ? 443 : $config['listener'];
        $certificatePrefix = empty($config['certificate_prefix']) ? 'acmephp_' : $config['certificate_prefix'];
        $cleanup = !isset($config['cleanup_old_certificate']) ? true : (bool) $config['cleanup_old_certificate'];
        $certificateName = $certificatePrefix.date('Ymd-His');

        $certificateArn = $this->uploadCertificate($response, $region, $certificateName);
        $this->installCertificate($certificateArn, $region, $loadBalancerName, $loadBalancerPort);

        if ($cleanup) {
            $this->cleanupOldCertificates($region, $certificatePrefix, $certificateName);
        }
    }

    private function uploadCertificate(CertificateResponse $response, $region, $certificateName)
    {
        $iamClient = $this->clientFactory->getIamClient($region);

        $issuerChain = [];
        $issuerCertificate = $response->getCertificate()->getIssuerCertificate();
        while (null !== $issuerCertificate) {
            $issuerChain[] = $issuerCertificate->getPEM();
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }
        $chainPem = implode("\n", $issuerChain);

        $response = $iamClient->uploadServerCertificate([
            'ServerCertificateName' => $certificateName,
            'CertificateBody' => $response->getCertificate()->getPEM(),
            'PrivateKey' => $response->getCertificateRequest()->getKeyPair()->getPrivateKey()->getPEM(),
            'CertificateChain' => $chainPem,
        ]);

        return $response['ServerCertificateMetadata']['Arn'];
    }

    private function cleanupOldCertificates($region, $certificatePrefix, $certificateName)
    {
        $iamClient = $this->clientFactory->getIamClient($region);

        $certificates = $iamClient->listServerCertificates()['ServerCertificateMetadataList'];
        foreach ($certificates as $certificate) {
            if (0 === strpos($certificate['ServerCertificateName'], $certificatePrefix)
                && $certificateName !== $certificate['ServerCertificateName']
            ) {
                try {
                    $this->retryCall(
                    // Try several time to delete certificate given AWS takes time to uninstall previous one
                        function () use ($iamClient, $certificate) {
                            $iamClient->deleteServerCertificate(
                                ['ServerCertificateName' => $certificate['ServerCertificateName']]
                            );
                        },
                        5
                    );
                } catch (IamException $e) {
                    if ('DeleteConflict' !== $e->getAwsErrorCode()) {
                        throw $e;
                    }
                }
            }
        }
    }

    abstract protected function installCertificate($certificateArn, $region, $loadBalancerName, $loadBalancerPort);

    protected function retryCall($callback, $retryCount = 10, $retrySleep = 1)
    {
        $lastException = null;
        for ($i = 0; $i < $retryCount; ++$i) {
            try {
                $callback();

                return;
            } catch (\Exception $e) {
                sleep($retrySleep);
                $lastException = $e;
            }
        }

        throw $lastException;
    }
}
