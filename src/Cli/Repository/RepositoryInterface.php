<?php

declare(strict_types=1);

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
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface RepositoryInterface
{
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    /**
     * Store a given certificate as associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function storeCertificateOrder(array $domains, CertificateOrder $order);

    /**
     * Check if there is a certificate order associated to given domains in the repository.
     */
    public function hasCertificateOrder(array $domains): bool;

    /**
     * Load the certificate irder associated to given domains.
     *
     * @throws AcmeCliException
     */
    public function loadCertificateOrder(array $domains): CertificateOrder;

    /**
     * Extract important elements from the given certificate response and store them
     * in the repository.
     *
     * This method will use the distinguished name common name as a domain to store:
     *      - the key pair
     *      - the certificate request
     *      - the certificate
     *
     * @throws AcmeCliException
     */
    public function storeCertificateResponse(CertificateResponse $certificateResponse);

    /**
     * Store a given key pair as the account key pair (the global key pair used to
     * interact with the ACME server).
     *
     * @throws AcmeCliException
     */
    public function storeAccountKeyPair(KeyPair $keyPair);

    /**
     * Check if there is an account key pair in the repository.
     */
    public function hasAccountKeyPair(): bool;

    /**
     * Load the account key pair.
     *
     * @throws AcmeCliException
     */
    public function loadAccountKeyPair(): KeyPair;

    /**
     * Store a given key pair as associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function storeDomainKeyPair(string $domain, KeyPair $keyPair);

    /**
     * Check if there is a key pair associated to the given domain in the repository.
     */
    public function hasDomainKeyPair(string $domain): bool;

    /**
     * Load the key pair associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function loadDomainKeyPair(string $domain): KeyPair;

    /**
     * Store a given authorization challenge as associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function storeDomainAuthorizationChallenge(string $domain, AuthorizationChallenge $authorizationChallenge);

    /**
     * Check if there is an authorization challenge associated to the given domain in the repository.
     */
    public function hasDomainAuthorizationChallenge(string $domain): bool;

    /**
     * Load the authorization challenge associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function loadDomainAuthorizationChallenge(string $domain): AuthorizationChallenge;

    /**
     * Store a given distinguished name as associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function storeDomainDistinguishedName(string $domain, DistinguishedName $distinguishedName);

    /**
     * Check if there is a distinguished name associated to the given domain in the repository.
     */
    public function hasDomainDistinguishedName(string $domain): bool;

    /**
     * Load the distinguished name associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function loadDomainDistinguishedName(string $domain): DistinguishedName;

    /**
     * Store a given certificate as associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function storeDomainCertificate(string $domain, Certificate $certificate);

    /**
     * Check if there is a certificate associated to the given domain in the repository.
     *
     * @return bool
     */
    public function hasDomainCertificate(string $domain);

    /**
     * Load the certificate associated to a given domain.
     *
     * @throws AcmeCliException
     */
    public function loadDomainCertificate(string $domain): Certificate;

    /**
     * Save a given string into a given path handling backup.
     */
    public function save(string $path, string $content, string $visibility = self::VISIBILITY_PRIVATE);
}
