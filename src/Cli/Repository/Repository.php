<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Repository;

use AcmePhp\Cli\Exception\AcmeCliException;
use AcmePhp\Cli\Serializer\PemEncoder;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Repository implements RepositoryInterface
{
    public const PATH_ACCOUNT_KEY_PRIVATE = 'account/key.private.pem';
    public const PATH_ACCOUNT_KEY_PUBLIC = 'account/key.public.pem';

    public const PATH_DOMAIN_KEY_PUBLIC = 'certs/{domain}/private/key.public.pem';
    public const PATH_DOMAIN_KEY_PRIVATE = 'certs/{domain}/private/key.private.pem';
    public const PATH_DOMAIN_CERT_CERT = 'certs/{domain}/public/cert.pem';
    public const PATH_DOMAIN_CERT_CHAIN = 'certs/{domain}/public/chain.pem';
    public const PATH_DOMAIN_CERT_FULLCHAIN = 'certs/{domain}/public/fullchain.pem';
    public const PATH_DOMAIN_CERT_COMBINED = 'certs/{domain}/private/combined.pem';

    public const PATH_CACHE_AUTHORIZATION_CHALLENGE = 'var/{domain}/authorization_challenge.json';
    public const PATH_CACHE_DISTINGUISHED_NAME = 'var/{domain}/distinguished_name.json';
    public const PATH_CACHE_CERTIFICATE_ORDER = 'var/{domains}/certificate_order.json';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly FilesystemOperator $storage,
    ) {
    }

    public function storeCertificateResponse(CertificateResponse $certificateResponse): void
    {
        $distinguishedName = $certificateResponse->getCertificateRequest()->getDistinguishedName();
        $domain = $distinguishedName->getCommonName();

        $this->storeDomainKeyPair($domain, $certificateResponse->getCertificateRequest()->getKeyPair());
        $this->storeDomainDistinguishedName($domain, $distinguishedName);
        $this->storeDomainCertificate($domain, $certificateResponse->getCertificate());
    }

    public function storeAccountKeyPair(KeyPair $keyPair): void
    {
        try {
            $this->save(
                self::PATH_ACCOUNT_KEY_PUBLIC,
                $this->serializer->serialize($keyPair->getPublicKey(), PemEncoder::FORMAT)
            );

            $this->save(
                self::PATH_ACCOUNT_KEY_PRIVATE,
                $this->serializer->serialize($keyPair->getPrivateKey(), PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException('Storing of account key pair failed', $e);
        }
    }

    public function hasAccountKeyPair(): bool
    {
        return $this->storage->has(self::PATH_ACCOUNT_KEY_PRIVATE);
    }

    public function loadAccountKeyPair(): KeyPair
    {
        try {
            $publicKeyPem = $this->storage->read(self::PATH_ACCOUNT_KEY_PUBLIC);
            $privateKeyPem = $this->storage->read(self::PATH_ACCOUNT_KEY_PRIVATE);

            return new KeyPair(
                $this->serializer->deserialize($publicKeyPem, PublicKey::class, PemEncoder::FORMAT),
                $this->serializer->deserialize($privateKeyPem, PrivateKey::class, PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException('Loading of account key pair failed', $e);
        }
    }

    public function storeDomainKeyPair(string $domain, KeyPair $keyPair): void
    {
        try {
            $this->save(
                $this->getPathForDomain(self::PATH_DOMAIN_KEY_PUBLIC, $domain),
                $this->serializer->serialize($keyPair->getPublicKey(), PemEncoder::FORMAT)
            );

            $this->save(
                $this->getPathForDomain(self::PATH_DOMAIN_KEY_PRIVATE, $domain),
                $this->serializer->serialize($keyPair->getPrivateKey(), PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s key pair failed', $domain), $e);
        }
    }

    public function hasDomainKeyPair(string $domain): bool
    {
        return $this->storage->has($this->getPathForDomain(self::PATH_DOMAIN_KEY_PRIVATE, $domain));
    }

    public function loadDomainKeyPair(string $domain): KeyPair
    {
        try {
            $publicKeyPem = $this->storage->read($this->getPathForDomain(self::PATH_DOMAIN_KEY_PUBLIC, $domain));
            $privateKeyPem = $this->storage->read($this->getPathForDomain(self::PATH_DOMAIN_KEY_PRIVATE, $domain));

            return new KeyPair(
                $this->serializer->deserialize($publicKeyPem, PublicKey::class, PemEncoder::FORMAT),
                $this->serializer->deserialize($privateKeyPem, PrivateKey::class, PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s key pair failed', $domain), $e);
        }
    }

    public function storeDomainAuthorizationChallenge(string $domain, AuthorizationChallenge $authorizationChallenge): void
    {
        try {
            $this->save(
                $this->getPathForDomain(self::PATH_CACHE_AUTHORIZATION_CHALLENGE, $domain),
                $this->serializer->serialize($authorizationChallenge, JsonEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s authorization challenge failed', $domain), $e);
        }
    }

    public function hasDomainAuthorizationChallenge(string $domain): bool
    {
        return $this->storage->has($this->getPathForDomain(self::PATH_CACHE_AUTHORIZATION_CHALLENGE, $domain));
    }

    public function loadDomainAuthorizationChallenge(string $domain): AuthorizationChallenge
    {
        try {
            $json = $this->storage->read($this->getPathForDomain(self::PATH_CACHE_AUTHORIZATION_CHALLENGE, $domain));

            return $this->serializer->deserialize($json, AuthorizationChallenge::class, JsonEncoder::FORMAT);
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s authorization challenge failed', $domain), $e);
        }
    }

    public function storeDomainDistinguishedName(string $domain, DistinguishedName $distinguishedName): void
    {
        try {
            $this->save(
                $this->getPathForDomain(self::PATH_CACHE_DISTINGUISHED_NAME, $domain),
                $this->serializer->serialize($distinguishedName, JsonEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s distinguished name failed', $domain), $e);
        }
    }

    public function hasDomainDistinguishedName(string $domain): bool
    {
        return $this->storage->has($this->getPathForDomain(self::PATH_CACHE_DISTINGUISHED_NAME, $domain));
    }

    public function loadDomainDistinguishedName(string $domain): DistinguishedName
    {
        try {
            $json = $this->storage->read($this->getPathForDomain(self::PATH_CACHE_DISTINGUISHED_NAME, $domain));

            return $this->serializer->deserialize($json, DistinguishedName::class, JsonEncoder::FORMAT);
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s distinguished name failed', $domain), $e);
        }
    }

    public function storeDomainCertificate(string $domain, Certificate $certificate): void
    {
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

        // Combined
        $keyPair = $this->loadDomainKeyPair($domain);
        $combinedPem = $fullChainPem.$this->serializer->serialize($keyPair->getPrivateKey(), PemEncoder::FORMAT);

        // Save
        $this->save($this->getPathForDomain(self::PATH_DOMAIN_CERT_CERT, $domain), $certPem);
        $this->save($this->getPathForDomain(self::PATH_DOMAIN_CERT_CHAIN, $domain), $chainPem);
        $this->save($this->getPathForDomain(self::PATH_DOMAIN_CERT_FULLCHAIN, $domain), $fullChainPem);
        $this->save($this->getPathForDomain(self::PATH_DOMAIN_CERT_COMBINED, $domain), $combinedPem);
    }

    public function hasDomainCertificate(string $domain): bool
    {
        return $this->storage->has($this->getPathForDomain(self::PATH_DOMAIN_CERT_FULLCHAIN, $domain));
    }

    public function loadDomainCertificate(string $domain): Certificate
    {
        try {
            $pems = explode('-----BEGIN CERTIFICATE-----', $this->storage->read($this->getPathForDomain(self::PATH_DOMAIN_CERT_FULLCHAIN, $domain)));
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s certificate failed', $domain), $e);
        }

        $pems = array_map(fn ($item): string => trim(str_replace('-----END CERTIFICATE-----', '', $item)), $pems);
        array_shift($pems);
        $pems = array_reverse($pems);

        $certificate = null;

        foreach ($pems as $pem) {
            $certificate = new Certificate(
                "-----BEGIN CERTIFICATE-----\n".$pem."\n-----END CERTIFICATE-----",
                $certificate
            );
        }

        return $certificate;
    }

    public function storeCertificateOrder(array $domains, CertificateOrder $order): void
    {
        try {
            $this->save(
                $this->getPathForDomainList(self::PATH_CACHE_CERTIFICATE_ORDER, $domains),
                $this->serializer->serialize($order, JsonEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domains %s certificate order failed', implode(', ', $domains)), $e);
        }
    }

    public function hasCertificateOrder(array $domains): bool
    {
        return $this->storage->has($this->getPathForDomainList(self::PATH_CACHE_CERTIFICATE_ORDER, $domains));
    }

    public function loadCertificateOrder(array $domains): CertificateOrder
    {
        try {
            $json = $this->storage->read($this->getPathForDomainList(self::PATH_CACHE_CERTIFICATE_ORDER, $domains));

            return $this->serializer->deserialize($json, CertificateOrder::class, JsonEncoder::FORMAT);
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domains %s certificate order failed', implode(', ', $domains)), $e);
        }
    }

    public function save(string $path, string $content, string $visibility = self::VISIBILITY_PRIVATE): void
    {
        $this->storage->write($path, $content);

        $this->storage->setVisibility($path, $visibility);
    }

    private function getPathForDomain(string $path, string $domain): string
    {
        return strtr($path, ['{domain}' => $this->normalizeDomain($domain)]);
    }

    private function getPathForDomainList(string $path, array $domains): string
    {
        return strtr($path, ['{domains}' => $this->normalizeDomainList($domains)]);
    }

    private function normalizeDomain($domain): string
    {
        return trim((string) $domain);
    }

    private function normalizeDomainList(array $domains): string
    {
        $normalizedDomains = array_unique(array_map([$this, 'normalizeDomain'], $domains));
        sort($normalizedDomains);

        return (isset($domains[0]) ? $this->normalizeDomain($domains[0]) : '-').'/'.sha1(json_encode($normalizedDomains));
    }
}
