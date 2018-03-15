<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core;

use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\AcmeClientV2Interface;
use AcmePhp\Core\Challenge\Http\SimpleHttpSolver;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\Client;

class AcmeClientTest extends AbstractFunctionnalTest
{
    /**
     * @var AcmeClientV2Interface
     */
    private $client;

    public function setUp()
    {
        $secureHttpClient = new SecureHttpClient(
            (new KeyPairGenerator())->generateKeyPair(),
            new Client(),
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            new ServerErrorHandler()
        );

        $this->client = new AcmeClient($secureHttpClient, 'https://localhost:14000/dir');
    }

    public function testFullProcess()
    {
        /*
         * Register account
         */
        $data = $this->client->registerAccount();

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('key', $data);

        $solver = new SimpleHttpSolver();
        /*
         * Ask for domain challenge
         */
        $order = $this->client->requestOrder(['acmephp.com']);
        $challenges = $order->getAuthorizationChallenges('acmephp.com');
        foreach ($challenges as $challenge) {
            if ('http-01' === $challenge->getType()) {
                break;
            }
        }

        $this->assertInstanceOf(AuthorizationChallenge::class, $challenge);
        $this->assertEquals('acmephp.com', $challenge->getDomain());
        $this->assertContains('https://localhost:14000/chalZ/', $challenge->getUrl());

        $solver->solve($challenge);

        /*
         * Challenge check
         */
        $process = $this->createServerProcess($challenge->getToken(), $challenge->getPayload());
        $process->start();
        sleep(1);
        $this->assertTrue($process->isRunning());

        try {
            $check = $this->client->challengeAuthorization($challenge);
            $this->assertEquals('valid', $check['status']);
        } finally {
            $process->stop();
        }

        /*
         * Request certificate
         */
        $csr = new CertificateRequest(new DistinguishedName('acmephp.com'), (new KeyPairGenerator())->generateKeyPair());
        $response = $this->client->finalizeOrder($order, $csr);

        $this->assertInstanceOf(CertificateResponse::class, $response);
        $this->assertEquals($csr, $response->getCertificateRequest());
        $this->assertInstanceOf(Certificate::class, $response->getCertificate());
    }
}
