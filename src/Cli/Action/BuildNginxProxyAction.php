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
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;

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

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function handle(array $config, CertificateResponse $response)
    {
        $domain = $response->getCertificateRequest()->getDistinguishedName()->getCommonName();
        $privateKey = $response->getCertificateRequest()->getKeyPair()->getPrivateKey();
        $certificate = $response->getCertificate();

        // To handle wildcard certs
        $domain = ltrim($domain, '*.');

        $this->repository->save('nginxproxy/'.$domain.'.key', $privateKey->getPEM());

        // Issuer chain
        $issuerChain = array_map(function (Certificate $certificate) {
            return $certificate->getPEM();
        }, $certificate->getIssuerChain());

        // Full chain
        $fullChainPem = $certificate->getPEM()."\n".implode("\n", $issuerChain);

        $this->repository->save('nginxproxy/'.$domain.'.crt', $fullChainPem);
    }
}
