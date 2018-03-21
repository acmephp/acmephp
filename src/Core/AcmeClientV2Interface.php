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
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;

/**
 * ACME protocol client interface.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface AcmeClientV2Interface extends AcmeClientInterface
{
    /**
     * Request authorization challenge data for a list of domains.
     *
     * An AuthorizationChallenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and you will then have
     * to expose the payload for the verification (see challengeAuthorization).
     *
     * @param string[] $domains The domains to challenge.
     *
     * @throws AcmeCoreServerException        When the ACME server returns an error HTTP status code
     *                                        (the exception will be more specific if detail is provided).
     * @throws AcmeCoreClientException        When an error occured during response parsing.
     * @throws ChallengeNotSupportedException When the HTTP challenge is not supported by the server.
     *
     * @return CertificateOrder The Order returned by the Certificate Authority.
     */
    public function requestOrder(array $domains);

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
     * @param CertificateOrder   $order   The Order returned by the Certificate Authority.
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
    public function finalizeOrder(CertificateOrder $order, CertificateRequest $csr, $timeout = 180);
}
