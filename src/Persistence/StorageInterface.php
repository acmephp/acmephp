<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence;

use AcmePhp\Persistence\Exception\StorageBackendException;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * Storage to store and organize certificates, key pairs and CSR.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface StorageInterface
{
    /**
     * Extract important elements from the given certificate response and store them
     * in the repository.
     *
     * This method will use the distinguished name common name as a domain to store:
     *      - the key pair
     *      - the certificate request
     *      - the certificate
     *
     * @param CertificateResponse $certificateResponse
     *
     * @throws \InvalidArgumentException When the distinguished name common name is not a valid domain.
     * @throws StorageBackendException   When an exception was thrown by the backend.
     *
     * @return void
     */
    public function storeCertificateResponse(CertificateResponse $certificateResponse);

    /**
     * Store a given key pair as the account key pair (the global key pair used to
     * interact with the ACME server).
     *
     * @param KeyPair $keyPair
     *
     * @throws StorageBackendException When an exception was thrown by the backend.
     *
     * @return void
     */
    public function storeAccountKeyPair(KeyPair $keyPair);

    /**
     * Store a given key as associated to a given domain.
     *
     * @param string  $domain
     * @param KeyPair $keyPair
     *
     * @throws \InvalidArgumentException When the domain is not a valid domain.
     * @throws StorageBackendException   When an exception was thrown by the backend.
     *
     * @return void
     */
    public function storeDomainKeyPair($domain, KeyPair $keyPair);

    /**
     * Store a given distinguished name as associated to a given domain.
     *
     * @param string            $domain
     * @param DistinguishedName $distinguishedName
     *
     * @throws \InvalidArgumentException When the domain is not a valid domain.
     * @throws StorageBackendException   When an exception was thrown by the backend.
     *
     * @return void
     */
    public function storeDomainDistinguishedName($domain, DistinguishedName $distinguishedName);

    /**
     * Store a given certificate as associated to a given domain.
     *
     * @param string      $domain
     * @param Certificate $certificate
     *
     * @throws \InvalidArgumentException When the domain is not a valid domain.
     * @throws StorageBackendException   When an exception was thrown by the backend.
     *
     * @return void
     */
    public function storeDomainCertificate($domain, Certificate $certificate);

    /**
     * Load the account key pair.
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return KeyPair
     */
    public function loadAccountKeyPair();

    /**
     * Load the account key pair.
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return bool
     */
    public function hasAccountKeyPair();

    /**
     * Load the key pair associated to a given domain.
     *
     * @param string $domain
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return KeyPair
     */
    public function loadDomainKeyPair($domain);

    /**
     * Load the key pair associated to a given domain.
     *
     * @param string $domain
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return bool
     */
    public function hasDomainKeyPair($domain);

    /**
     * Load the distinguished name associated to a given domain.
     *
     * @param string $domain
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return DistinguishedName
     */
    public function loadDomainDistinguishedName($domain);

    /**
     * Load the distinguished name associated to a given domain.
     *
     * @param string $domain
     *
     * @throws StorageBackendException When the key pair files don't exist or when an exception was thrown by the backend.
     *
     * @return bool
     */
    public function hasDomainDistinguishedName($domain);
}
