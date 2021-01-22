<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

/**
 * ACME protocol client interface.
 *
 * @author Werner Röttcher <wernerrottcher@gmail.com>
 */
interface MultipleAuthorizationClientInterface extends AcmeClientInterface
{
    /**
     * Request authorization challenge data for a list of given domains.
     *
     * An AuthorizationChallenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and you will then have
     * to expose the payload for the verification (see challengeAuthorization).
     *
     * @param array $domain the domains to challenge
     *
     * @throws AcmeCoreServerException        when the ACME server returns an error HTTP status code
     *                                        (the exception will be more specific if detail is provided)
     * @throws AcmeCoreClientException        when an error occurred during response parsing
     * @throws ChallengeNotSupportedException when the HTTP challenge is not supported by the server
     *
     * @return AuthorizationChallenges[] the list of authorization challenge group data per domain as returned by the Certificate Authority
     */
    public function requestMultipleAuthorization(array $domains): array;
}
