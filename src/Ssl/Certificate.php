<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

use AcmePhp\Ssl\Exception\CertificateFormatException;
use Webmozart\Assert\Assert;

/**
 * Represent a Certificate.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Certificate
{
    /** @var string */
    private $certificatePEM;

    /** @var Certificate */
    private $issuerCertificate;

    public function __construct(string $certificatePEM, ?self $issuerCertificate = null)
    {
        Assert::stringNotEmpty($certificatePEM, __CLASS__.'::$certificatePEM should not be an empty string. Got %s');

        $this->certificatePEM = $certificatePEM;
        $this->issuerCertificate = $issuerCertificate;
    }

    /**
     * @return Certificate[]
     */
    public function getIssuerChain(): array
    {
        $chain = [];
        $issuerCertificate = $this->getIssuerCertificate();

        while (null !== $issuerCertificate) {
            $chain[] = $issuerCertificate;
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }

        return $chain;
    }

    public function getPEM(): string
    {
        return $this->certificatePEM;
    }

    public function getIssuerCertificate(): ?self
    {
        return $this->issuerCertificate;
    }

    /**
     * @return resource
     */
    public function getPublicKeyResource()
    {
        if (!$resource = openssl_pkey_get_public($this->certificatePEM)) {
            throw new CertificateFormatException(sprintf('Failed to convert certificate into public key resource: %s', openssl_error_string()));
        }

        return $resource;
    }

    public function getPublicKey(): PublicKey
    {
        return new PublicKey(openssl_pkey_get_details($this->getPublicKeyResource())['key']);
    }
}
