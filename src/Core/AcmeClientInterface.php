<?php

/*
 * This file is part of the ACME PHP library.
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
use AcmePhp\Core\Exception\Protocol\HttpChallengeFailedException;
use AcmePhp\Core\Exception\Protocol\HttpChallengeNotSupportedException;
use AcmePhp\Core\Exception\Protocol\HttpChallengeTimedOutException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
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
     * @param string|null $agreement An optionnal URI referring to a subscriber agreement or terms of service.
     * @param string|null $email     An optionnal e-mail to associate with the account.
     *
     * @throws AcmeCoreServerException When the ACME server returns an error HTTP status code
     *                                 (the exception will be more specific if detail is provided).
     * @throws AcmeCoreClientException When an error occured during response parsing.
     *
     * @return array The Certificate Authority response decoded from JSON into an array.
     */
    public function registerAccount($agreement = null, $email = null);

    /**
     * Request authorization challenge data for a given domain.
     *
     * An AuthorizationChallenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and you will then have
     * to expose the payload for the verification (see challengeAuthorization).
     *
     * @param string $domain The domain to challenge.
     *
     * @throws AcmeCoreServerException            When the ACME server returns an error HTTP status code
     *                                            (the exception will be more specific if detail is provided).
     * @throws AcmeCoreClientException            When an error occured during response parsing.
     * @throws HttpChallengeNotSupportedException When the HTTP challenge is not supported by the server.
     *
     * @return AuthorizationChallenge The data returned by the Certificate Authority.
     */
    public function requestAuthorization($domain);

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
     * @param AuthorizationChallenge $challenge The challenge data to check.
     * @param int                    $timeout   The timeout period.
     *
     * @throws AcmeCoreServerException        When the ACME server returns an error HTTP status code
     *                                        (the exception will be more specific if detail is provided).
     * @throws AcmeCoreClientException        When an error occured during response parsing.
     * @throws HttpChallengeTimedOutException When the challenge timed out.
     * @throws HttpChallengeFailedException   When the challenge failed.
     *
     * @return array The decoded server response (containing the result of the check).
     */
    public function challengeAuthorization(AuthorizationChallenge $challenge, $timeout = 180);

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
     * @param string             $domain  The domain to request a certificate for.
     * @param CertificateRequest $csr     The Certificate Signing Request (informations for the certificate).
     * @param int                $timeout The timeout period.
     *
     * @throws AcmeCoreServerException             When the ACME server returns an error HTTP status code
     *                                             (the exception will be more specific if detail is provided).
     * @throws AcmeCoreClientException             When an error occured during response parsing.
     * @throws CertificateRequestFailedException   When the certificate request failed.
     * @throws CertificateRequestTimedOutException When the certificate request timed out.
     *
     * @return CertificateResponse The certificate data to save it somewhere you want.
     */
    public function requestCertificate($domain, CertificateRequest $csr, $timeout = 180);
}
