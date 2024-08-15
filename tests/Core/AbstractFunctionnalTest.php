<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

abstract class AbstractFunctionnalTest extends TestCase
{
    protected function handleChallenge($token, $payload)
    {
        $fakeServer = new Client();
        $response = $fakeServer->post('http://localhost:8055/add-http01', array(RequestOptions::JSON => array('token' => $token, 'content' => $payload)));

        $this->assertSame(200, $response->getStatusCode());
    }

    protected function cleanChallenge($token)
    {
        $fakeServer = new Client();
        $response = $fakeServer->post('http://localhost:8055/del-http01', array(RequestOptions::JSON => array('token' => $token)));

        $this->assertSame(200, $response->getStatusCode());
    }
}
