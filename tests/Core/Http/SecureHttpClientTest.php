<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Http;

use AcmePhp\Core\Exception\AcmeCoreException;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

class SecureHttpClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $responses
     * @param bool  $willThrow
     *
     * @return SecureHttpClient
     */
    private function createMockedClient(array $responses, $willThrow = false)
    {
        $keyPairGenerator = new KeyPairGenerator();
        $httpClient = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);

        $errorHandler = $this->getMockBuilder(ServerErrorHandler::class)->getMock();

        if ($willThrow) {
            $errorHandler->expects($this->once())
                ->method('createAcmeExceptionForResponse')
                ->willReturn(new AcmeCoreException());
        }

        return new SecureHttpClient(
            $keyPairGenerator->generateKeyPair(),
            $httpClient,
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            $errorHandler
        );
    }

    public function testValidUnsignedStringRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], 'foo')], false);
        $body = $client->unsignedRequest('GET', '/foo', ['foo' => 'bar'], false);
        $this->assertEquals('foo', $body);
    }

    public function testValidUnsignedJsonRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], json_encode(['test' => 'ok']))], false);
        $data = $client->unsignedRequest('GET', '/foo', ['foo' => 'bar'], true);
        $this->assertEquals(['test' => 'ok'], $data);
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Protocol\ExpectedJsonException
     */
    public function testInvalidUnsignedJsonRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], 'invalid json')], false);
        $client->unsignedRequest('GET', '/foo', ['foo' => 'bar'], true);
    }

    public function testValidSignedStringRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], 'foo')], false);
        $body = $client->signedRequest('GET', '/foo', ['foo' => 'bar'], false);
        $this->assertEquals('foo', $body);
    }

    public function testValidSignedJsonRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], json_encode(['test' => 'ok']))], false);
        $data = $client->signedRequest('GET', '/foo', ['foo' => 'bar'], true);
        $this->assertEquals(['test' => 'ok'], $data);
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Protocol\ExpectedJsonException
     */
    public function testInvalidSignedJsonRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], 'invalid json')], false);
        $client->signedRequest('GET', '/foo', ['foo' => 'bar'], true);
    }

    public function testSignedRequestPayload()
    {
        $container = [];

        $stack = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(['test' => 'ok']))]));
        $stack->push(Middleware::history($container));

        $keyPairGenerator = new KeyPairGenerator();

        $dataSigner = $this->getMockBuilder(DataSigner::class)->getMock();
        $dataSigner->expects($this->once())
            ->method('signData')
            ->willReturn('foobar');

        $client = new SecureHttpClient(
            $keyPairGenerator->generateKeyPair(),
            new Client(['handler' => $stack]),
            new Base64SafeEncoder(),
            new KeyParser(),
            $dataSigner,
            $this->getMockBuilder(ServerErrorHandler::class)->getMock()
        );

        $client->signedRequest('POST', '/acme/new-reg', ['contact' => 'foo@bar.com'], true);

        // Check request object
        $this->assertCount(1, $container);

        /** @var RequestInterface $request */
        $request = $container[0]['request'];

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/acme/new-reg', ($request->getUri() instanceof Uri) ? $request->getUri()->getPath() : $request->getUri());

        $body = \GuzzleHttp\Psr7\copy_to_string($request->getBody());
        $payload = @json_decode($body, true);

        $this->assertInternalType('array', $payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertEquals('Zm9vYmFy', $payload['signature']);
    }
}
