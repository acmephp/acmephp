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
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Repository implements RepositoryInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var FilesystemInterface
     */
    private $master;

    /**
     * @var FilesystemInterface
     */
    private $backup;

    /**
     * @var bool
     */
    private $enableBackup;

    /**
     * @param SerializerInterface $serializer
     * @param FilesystemInterface $master
     * @param FilesystemInterface $backup
     * @param bool                $enableBackup
     */
    public function __construct(SerializerInterface $serializer, FilesystemInterface $master, FilesystemInterface $backup, $enableBackup)
    {
        $this->serializer = $serializer;
        $this->master = $master;
        $this->backup = $backup;
        $this->enableBackup = $enableBackup;
    }

    /**
     * {@inheritdoc}
     */
    public function storeCertificateResponse(CertificateResponse $certificateResponse)
    {
        $distinguishedName = $certificateResponse->getCertificateRequest()->getDistinguishedName();
        $domain = $distinguishedName->getCommonName();

        $this->storeDomainKeyPair($domain, $certificateResponse->getCertificateRequest()->getKeyPair());
        $this->storeDomainDistinguishedName($domain, $distinguishedName);
        $this->storeDomainCertificate($domain, $certificateResponse->getCertificate());
    }

    /**
     * {@inheritdoc}
     */
    public function storeAccountKeyPair(KeyPair $keyPair)
    {
        try {
            $this->save(
                'private/_account/public.pem',
                $this->serializer->serialize($keyPair->getPublicKey(), PemEncoder::FORMAT)
            );

            $this->save(
                'private/_account/private.pem',
                $this->serializer->serialize($keyPair->getPrivateKey(), PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException('Storing of account key pair failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccountKeyPair()
    {
        return $this->master->has('private/_account/private.pem');
    }

    /**
     * {@inheritdoc}
     */
    public function loadAccountKeyPair()
    {
        try {
            $publicKeyPem = $this->master->read('private/_account/public.pem');
            $privateKeyPem = $this->master->read('private/_account/private.pem');

            return new KeyPair(
                $this->serializer->deserialize($publicKeyPem, PublicKey::class, PemEncoder::FORMAT),
                $this->serializer->deserialize($privateKeyPem, PrivateKey::class, PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException('Loading of account key pair failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainKeyPair($domain, KeyPair $keyPair)
    {
        try {
            $this->save(
                'private/'.$domain.'/public.pem',
                $this->serializer->serialize($keyPair->getPublicKey(), PemEncoder::FORMAT)
            );

            $this->save(
                'private/'.$domain.'/private.pem',
                $this->serializer->serialize($keyPair->getPrivateKey(), PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s key pair failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainKeyPair($domain)
    {
        return $this->master->has('private/'.$domain.'/private.pem');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainKeyPair($domain)
    {
        try {
            $publicKeyPem = $this->master->read('private/'.$domain.'/public.pem');
            $privateKeyPem = $this->master->read('private/'.$domain.'/private.pem');

            return new KeyPair(
                $this->serializer->deserialize($publicKeyPem, PublicKey::class, PemEncoder::FORMAT),
                $this->serializer->deserialize($privateKeyPem, PrivateKey::class, PemEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s key pair failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainAuthorizationChallenge($domain, AuthorizationChallenge $authorizationChallenge)
    {
        try {
            $this->save(
                'private/'.$domain.'/authorization_challenge.json',
                $this->serializer->serialize($authorizationChallenge, JsonEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s authorization challenge failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainAuthorizationChallenge($domain)
    {
        return $this->master->has('private/'.$domain.'/authorization_challenge.json');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainAuthorizationChallenge($domain)
    {
        try {
            $json = $this->master->read('private/'.$domain.'/authorization_challenge.json');

            return $this->serializer->deserialize($json, AuthorizationChallenge::class, JsonEncoder::FORMAT);
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s authorization challenge failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainDistinguishedName($domain, DistinguishedName $distinguishedName)
    {
        try {
            $this->save(
                'private/'.$domain.'/distinguished_name.json',
                $this->serializer->serialize($distinguishedName, JsonEncoder::FORMAT)
            );
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Storing of domain %s distinguished name failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainDistinguishedName($domain)
    {
        return $this->master->has('private/'.$domain.'/distinguished_name.json');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainDistinguishedName($domain)
    {
        try {
            $json = $this->master->read('private/'.$domain.'/distinguished_name.json');

            return $this->serializer->deserialize($json, DistinguishedName::class, JsonEncoder::FORMAT);
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s distinguished name failed', $domain), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainCertificate($domain, Certificate $certificate)
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
        $this->save('certs/'.$domain.'/cert.pem', $certPem);
        $this->save('certs/'.$domain.'/chain.pem', $chainPem);
        $this->save('certs/'.$domain.'/fullchain.pem', $fullChainPem);
        $this->save('certs/'.$domain.'/combined.pem', $combinedPem);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainCertificate($domain)
    {
        return $this->master->has('certs/'.$domain.'/fullchain.pem');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainCertificate($domain)
    {
        try {
            $pems = explode('-----BEGIN CERTIFICATE-----', $this->master->read('certs/'.$domain.'/fullchain.pem'));
        } catch (\Exception $e) {
            throw new AcmeCliException(sprintf('Loading of domain %s certificate failed', $domain), $e);
        }

        $pems = array_map(function ($item) {
            return trim(str_replace('-----END CERTIFICATE-----', '', $item));
        }, $pems);
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

    /**
     * {@inheritdoc}
     */
    public function save($path, $content, $visibility = self::VISIBILITY_PRIVATE)
    {
        if (!$this->master->has($path)) {
            // File creation: remove from backup if it existed and warm-up both master and backup
            if ($this->enableBackup) {
                if ($this->backup->has($path)) {
                    $this->backup->delete($path);
                }

                $this->backup->write($path, $content);
            }

            $this->master->write($path, $content);
        } else {
            // File update: backup before writing
            if ($this->enableBackup) {
                $oldContent = $this->master->read($path);

                if ($oldContent !== false) {
                    if ($this->backup->has($path)) {
                        $this->backup->update($path, $oldContent);
                    } else {
                        $this->backup->write($path, $oldContent);
                    }
                }
            }

            $this->master->update($path, $content);
        }

        if ($this->enableBackup) {
            $this->backup->setVisibility($path, $visibility);
        }

        $this->master->setVisibility($path, $visibility);
    }
}
