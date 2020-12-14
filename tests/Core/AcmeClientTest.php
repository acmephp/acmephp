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

use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\Challenge\Http\SimpleHttpSolver;
use AcmePhp\Core\Exception\Protocol\CertificateRevocationException;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\ExternalAccount;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\KeyOption;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\Client;

class AcmeClientTest extends AbstractFunctionnalTest
{
    public function provideFullProcess()
    {
        yield 'rsa1024' => [new RsaKeyOption(1024), false];
        yield 'rsa1024-alternate' => [new RsaKeyOption(1024), true];
        yield 'rsa4098' => [new RsaKeyOption(4098), false];
        yield 'ecprime256v1' => [new EcKeyOption('prime256v1'), false];
        yield 'ecsecp384r1' => [new EcKeyOption('secp384r1'), false];
    }

    /**
     * @dataProvider provideFullProcess
     */
    public function testFullProcess(KeyOption $keyOption, bool $useAlternateCertificate)
    {
        $secureHttpClient = new SecureHttpClient(
            (new KeyPairGenerator())->generateKeyPair($keyOption),
            new Client(),
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            new ServerErrorHandler()
        );

        $client = new AcmeClient($secureHttpClient, 'https://localhost:14000/dir');

        /*
         * Register account
         */
        if ('eab' === getenv('PEBBLE_MODE')) {
            $data = $client->registerAccount('titouan.galopin@acmephp.com', new ExternalAccount('kid1', 'dGVzdGluZw'));
        } else {
            $data = $client->registerAccount('titouan.galopin@acmephp.com');
        }

        $this->assertIsArray($data);
        $this->assertArrayHasKey('key', $data);

        $solver = new SimpleHttpSolver();

        /*
         * Ask for domain challenge
         */
        $order = $client->requestOrder(['acmephp.com']);
        $challenges = $order->getAuthorizationChallenges('acmephp.com');
        foreach ($challenges as $challenge) {
            if ('http-01' === $challenge->getType()) {
                break;
            }
        }

        $this->assertInstanceOf(AuthorizationChallenge::class, $challenge);
        $this->assertEquals('acmephp.com', $challenge->getDomain());
        $this->assertStringContainsString('https://localhost:14000/chalZ/', $challenge->getUrl());

        $solver->solve($challenge);

        /*
         * Challenge check
         */
        $this->handleChallenge($challenge->getToken(), $challenge->getPayload());
        try {
            $check = $client->challengeAuthorization($challenge);
            $this->assertEquals('valid', $check['status']);
        } finally {
            $this->cleanChallenge($challenge->getToken());
        }

        /*
         * Request certificate
         */
        $csr = new CertificateRequest(new DistinguishedName('acmephp.com'), (new KeyPairGenerator())->generateKeyPair($keyOption));
        $response = $client->finalizeOrder($order, $csr, 180, $useAlternateCertificate);

        $this->assertInstanceOf(CertificateResponse::class, $response);
        $this->assertEquals($csr, $response->getCertificateRequest());
        $this->assertInstanceOf(Certificate::class, $response->getCertificate());

        /*
         * Revoke certificate
         *
         * ACME will not let you revoke the same cert twice so this test should pass both cases
         */
        try {
            $client->revokeCertificate($response->getCertificate());
        } catch (CertificateRevocationException $e) {
            $this->assertStringContainsString('Unable to find specified certificate', $e->getPrevious()->getPrevious()->getMessage());
        }
    }
}
