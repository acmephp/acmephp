<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * Extract data needed to solve HTTP challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class HttpDataExtractor
{
    /**
     * Retrieves the absolute URL called by the CA.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     *
     * @return string
     */
    public function getCheckUrl(AuthorizationChallenge $authorizationChallenge)
    {
        return sprintf(
            'http://%s/.well-known/acme-challenge/%s',
            $authorizationChallenge->getDomain(),
            $authorizationChallenge->getToken()
        );
    }

    /**
     * Retrieves the content that should be returned in the response.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     *
     * @return string
     */
    public function getCheckContent(AuthorizationChallenge $authorizationChallenge)
    {
        return $authorizationChallenge->getPayload();
    }
}
