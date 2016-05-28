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
use AcmePhp\Core\AcmeClientInterface;
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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AcmeClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AcmeClientInterface
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

        $this->client = new AcmeClient($secureHttpClient, 'http://127.0.0.1:4000/directory');
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Server\MalformedServerException
     */
    public function testDoubleRegisterAccountFail()
    {
        $this->client->registerAccount();
        $this->client->registerAccount();
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Server\MalformedServerException
     */
    public function testInvalidAgreement()
    {
        $this->client->registerAccount('http://invalid.com');
        $this->client->requestAuthorization('example.org');
    }

    public function testFullProcess()
    {
        /*
         * Register account
         */
        $data = $this->client->registerAccount('http://boulder:4000/terms/v1');

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('key', $data);
        $this->assertArrayHasKey('initialIp', $data);
        $this->assertArrayHasKey('createdAt', $data);

        /*
         * Ask for domain challenge
         */
        $challenge = $this->client->requestAuthorization('acmephp.com');

        $this->assertInstanceOf(AuthorizationChallenge::class, $challenge);
        $this->assertEquals('acmephp.com', $challenge->getDomain());
        $this->assertContains('http://127.0.0.1:4000/acme/challenge', $challenge->getUrl());
        $this->assertContains('http://127.0.0.1:4000/acme/authz', $challenge->getLocation());

        /*
         * Challenge check
         */
        $process = $this->createServerProcess($challenge);
        $process->start();

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
        $response = $this->client->requestCertificate('acmephp.com', $csr);

        $this->assertInstanceOf(CertificateResponse::class, $response);
        $this->assertEquals($csr, $response->getCertificateRequest());
        $this->assertInstanceOf(Certificate::class, $response->getCertificate());
        $this->assertInstanceOf(Certificate::class, $response->getCertificate()->getIssuerCertificate());
    }

    /**
     * @param AuthorizationChallenge $challenge
     *
     * @return Process
     */
    private function createServerProcess(AuthorizationChallenge $challenge)
    {
        $listen = '0.0.0.0:5002';
        $documentRoot = __DIR__.'/Fixtures/challenges';

        // Create file
        file_put_contents($documentRoot.'/.well-known/acme-challenge/'.$challenge->getToken(), $challenge->getPayload());

        // Start server
        $finder = new PhpExecutableFinder();

        if (false === $binary = $finder->find()) {
            throw new \RuntimeException('Unable to find PHP binary to start server.');
        }

        $script = implode(' ', array_map(['Symfony\Component\Process\ProcessUtils', 'escapeArgument'], [
            $binary,
            '-S',
            $listen,
            '-t',
            $documentRoot,
        ]));

        return new Process('exec '.$script, $documentRoot, null, null, null);
    }
}
