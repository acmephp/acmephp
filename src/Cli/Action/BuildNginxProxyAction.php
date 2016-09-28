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

use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Cli\Serializer\PemEncoder;
use AcmePhp\Ssl\CertificateResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Action to create an nginx-proxy compatible directory.
 *
 * @see https://github.com/jwilder/nginx-proxy
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class BuildNginxProxyAction implements ActionInterface
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param RepositoryInterface $repository
     * @param SerializerInterface $serializer
     */
    public function __construct(RepositoryInterface $repository, SerializerInterface $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'build_nginxproxy';
    }

    /**
     * {@inheritdoc}
     */
    public function handle($config, CertificateResponse $response)
    {
        $domain = $response->getCertificateRequest()->getDistinguishedName()->getCommonName();
        $privateKey = $response->getCertificateRequest()->getKeyPair()->getPrivateKey();
        $certificate = $response->getCertificate();

        $this->repository->save(
            'nginxproxy/'.$domain.'.key',
            $this->serializer->serialize($privateKey, PemEncoder::FORMAT)
        );

        // Simple certificate
        $certPem = $this->serializer->serialize($certificate, PemEncoder::FORMAT);

        // Issuer chain
        $issuerChain = [];
        $issuerCertificate = $certificate->getIssuerCertificate();

        while (null !== $issuerCertificate) {
            $issuerChain[] = $this->serializer->serialize($issuerCertificate, PemEncoder::FORMAT);
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }

        $chainPem = implode("\n", $issuerChain);

        // Full chain
        $fullChainPem = $certPem.$chainPem;

        $this->repository->save('nginxproxy/'.$domain.'.crt', $fullChainPem);
    }
}
