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

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * ACME HTTP solver talking to pebble-challtestsrv.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class MockServerHttpSolver implements SolverInterface
{
    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'http-01' === $authorizationChallenge->getType();
    }

    public function solve(AuthorizationChallenge $authorizationChallenge): void
    {
        (new Client())->post('http://localhost:8055/add-http01', [
            RequestOptions::JSON => [
                'token' => $authorizationChallenge->getToken(),
                'content' => $authorizationChallenge->getPayload(),
            ],
        ]);
    }

    public function cleanup(AuthorizationChallenge $authorizationChallenge): void
    {
        (new Client())->post('http://localhost:8055/del-http01', [
            RequestOptions::JSON => [
                'token' => $authorizationChallenge->getToken(),
            ],
        ]);
    }
}
