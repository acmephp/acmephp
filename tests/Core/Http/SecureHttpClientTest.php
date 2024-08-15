<?php

/*
 * This file is part of the Acme PHP project.
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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class SecureHttpClientTest extends TestCase
{
    /**
     * @param bool $willThrow
     *
     * @return SecureHttpClient
     */
    private function createMockedClient(array $responses, $willThrow = false)
    {
        $keyPairGenerator = new KeyPairGenerator();
        $httpClient = new Client(array('handler' => HandlerStack::create(new MockHandler($responses))));

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

    public function testSignKidPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signKidPayload('/foo', 'account', array('foo' => 'bar'));

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload']));
    }

    public function testSignKidPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signKidPayload('/foo', 'account', array());

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload']));
    }

    public function testSignKidPayloadWithNullPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signKidPayload('/foo', 'account');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signJwkPayload('/foo', array('foo' => 'bar'));

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signJwkPayload('/foo', array());

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayloadWithNullPayload()
    {
        $client = $this->createMockedClient(array());
        $payload = $client->signJwkPayload('/foo');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload']));
    }

    public function testValidStringRequest()
    {
        $client = $this->createMockedClient(array(new Response(200, array(), 'foo')), false);
        $body = $client->request('GET', '/foo', array('foo' => 'bar'), false);
        $this->assertEquals('foo', $body);
    }

    public function testValidJsonRequest()
    {
        $client = $this->createMockedClient(array(new Response(200, array(), json_encode(array('test' => 'ok')))), false);
        $data = $client->request('GET', '/foo', array('foo' => 'bar'), true);
        $this->assertEquals(array('test' => 'ok'), $data);
    }

    public function testInvalidJsonRequest()
    {
        $this->expectException('AcmePhp\Core\Exception\Protocol\ExpectedJsonException');
        $client = $this->createMockedClient(array(new Response(200, array(), 'invalid json')), false);
        $client->request('GET', '/foo', array('foo' => 'bar'), true);
    }

    public function testRequestPayload()
    {
        $container = array();

        $stack = HandlerStack::create(new MockHandler(array(new Response(200, array(), json_encode(array('test' => 'ok'))))));
        $stack->push(Middleware::history($container));

        $keyPairGenerator = new KeyPairGenerator();

        $dataSigner = $this->getMockBuilder(DataSigner::class)->getMock();
        $dataSigner->expects($this->once())
            ->method('signData')
            ->willReturn('foobar');

        $client = new SecureHttpClient(
            $keyPairGenerator->generateKeyPair(),
            new Client(array('handler' => $stack)),
            new Base64SafeEncoder(),
            new KeyParser(),
            $dataSigner,
            $this->getMockBuilder(ServerErrorHandler::class)->getMock()
        );

        $client->request('POST', '/acme/new-reg', $client->signJwkPayload('/acme/new-reg', array('contact' => 'foo@bar.com')), true);

        // Check request object
        $this->assertCount(1, $container);

        /** @var RequestInterface $request */
        $request = $container[0]['request'];

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/acme/new-reg', ($request->getUri() instanceof Uri) ? $request->getUri()->getPath() : $request->getUri());

        $body = \GuzzleHttp\Psr7\copy_to_string($request->getBody());
        $payload = @json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertEquals('Zm9vYmFy', $payload['signature']);
    }
}
