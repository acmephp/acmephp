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

namespace AcmePhp\Core;

use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestFailedException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestTimedOutException;
use AcmePhp\Core\Exception\Protocol\CertificateRevocationException;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Core\Protocol\ExternalAccount;
use AcmePhp\Core\Protocol\RevocationReason;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;

/**
 * ACME protocol client interface.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface AcmeClientInterface
{
    /**
     * Register the local account KeyPair in the Certificate Authority.
     *
     * @param string|null          $email           an optionnal e-mail to associate with the account
     * @param ExternalAccount|null $externalAccount an optionnal External Account to use for External Account Binding
     *
     * @return array the Certificate Authority response decoded from JSON into an array
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     *                                 (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException when an error occured during response parsing
     */
    public function registerAccount(?string $email = null, ?ExternalAccount $externalAccount = null): array;

    /**
     * Request authorization challenge data for a list of domains.
     *
     * An AuthorizationChallenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and you will then have
     * to expose the payload for the verification (see challengeAuthorization).
     *
     * @param string[] $domains the domains to challenge
     *
     * @return CertificateOrder the Order returned by the Certificate Authority
     *
     * @throws AcmeCoreServerException        when the ACME server returns an error HTTP status code
     *                                        (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException        when an error occured during response parsing
     * @throws ChallengeNotSupportedException when the HTTP challenge is not supported by the server
     */
    public function requestOrder(array $domains): CertificateOrder;

    /**
     * Request the current status of a certificate order.
     */
    public function reloadOrder(CertificateOrder $order): CertificateOrder;

    /**
     * Request a certificate for the given domain.
     *
     * This method should be called only if a previous authorization challenge has
     * been successful for the asked domain.
     *
     * WARNING : This method SHOULD NOT BE USED in a web action. It will
     * wait for the Certificate Authority to validate the certificate and
     * this operation could be long.
     *
     * @param CertificateOrder   $order                                 the Order returned by the Certificate Authority
     * @param CertificateRequest $csr                                   the Certificate Signing Request (informations for the certificate)
     * @param int                $timeout                               the timeout period
     * @param bool               $returnAlternateCertificateIfAvailable whether the alternate certificate provided by
     *                                                                  the CA should be returned instead of the main one.
     *                                                                  This is especially useful following
     *                                                                  https://letsencrypt.org/2019/04/15/transitioning-to-isrg-root.html.
     *
     * @return CertificateResponse the certificate data to save it somewhere you want
     *
     * @throws AcmeCoreServerException             when the ACME server returns an error HTTP status code
     *                                             (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException             when an error occured during response parsing
     * @throws CertificateRequestFailedException   when the certificate request failed
     * @throws CertificateRequestTimedOutException when the certificate request timed out
     */
    public function finalizeOrder(CertificateOrder $order, CertificateRequest $csr, int $timeout = 180, bool $returnAlternateCertificateIfAvailable = false): CertificateResponse;

    /**
     * Request authorization challenge data for a given domain.
     *
     * An AuthorizationChallenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and you will then have
     * to expose the payload for the verification (see challengeAuthorization).
     *
     * @param string $domain the domain to challenge
     *
     * @return AuthorizationChallenge[] the list of challenges data returned by the Certificate Authority
     *
     * @throws AcmeCoreServerException        when the ACME server returns an error HTTP status code
     *                                        (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException        when an error occured during response parsing
     * @throws ChallengeNotSupportedException when the HTTP challenge is not supported by the server
     */
    public function requestAuthorization(string $domain): array;

    /**
     * Request the current status of an authorization challenge.
     *
     * @param AuthorizationChallenge $challenge The challenge to request
     *
     * @return AuthorizationChallenge A new instance of the challenge
     */
    public function reloadAuthorization(AuthorizationChallenge $challenge): AuthorizationChallenge;

    /**
     * Ask the Certificate Authority to challenge a given authorization.
     *
     * This check will generally consists of requesting over HTTP the domain
     * at a specific URL. This URL should return the raw payload generated
     * by requestAuthorization.
     *
     * WARNING : This method SHOULD NOT BE USED in a web action. It will
     * wait for the Certificate Authority to validate the challenge and this
     * operation could be long.
     *
     * @param AuthorizationChallenge $challenge the challenge data to check
     * @param int                    $timeout   the timeout period
     *
     * @return array the validate challenge response
     *
     * @throws AcmeCoreServerException    when the ACME server returns an error HTTP status code
     *                                    (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException    when an error occured during response parsing
     * @throws ChallengeTimedOutException when the challenge timed out
     * @throws ChallengeFailedException   when the challenge failed
     */
    public function challengeAuthorization(AuthorizationChallenge $challenge, int $timeout = 180): array;

    /**
     * Request a certificate for the given domain.
     *
     * This method should be called only if a previous authorization challenge has
     * been successful for the asked domain.
     *
     * WARNING : This method SHOULD NOT BE USED in a web action. It will
     * wait for the Certificate Authority to validate the certificate and
     * this operation could be long.
     *
     * @param string             $domain                                the domain to request a certificate for
     * @param CertificateRequest $csr                                   the Certificate Signing Request (informations for the certificate)
     * @param int                $timeout                               the timeout period
     * @param bool               $returnAlternateCertificateIfAvailable whether the alternate certificate provided by
     *                                                                  the CA should be returned instead of the main one.
     *                                                                  This is especially useful following
     *                                                                  https://letsencrypt.org/2019/04/15/transitioning-to-isrg-root.html.
     *
     * @return CertificateResponse the certificate data to save it somewhere you want
     *
     * @throws AcmeCoreServerException             when the ACME server returns an error HTTP status code
     *                                             (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException             when an error occured during response parsing
     * @throws CertificateRequestFailedException   when the certificate request failed
     * @throws CertificateRequestTimedOutException when the certificate request timed out
     */
    public function requestCertificate(string $domain, CertificateRequest $csr, int $timeout = 180, bool $returnAlternateCertificateIfAvailable = false): CertificateResponse;

    /**
     * Revoke a given certificate from the Certificate Authority.
     *
     * @throws CertificateRevocationException
     */
    public function revokeCertificate(Certificate $certificate, ?RevocationReason $revocationReason = null);
}
