<?php

declare(strict_types=1);

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
use AcmePhp\Core\Protocol\ExternalAccount;
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
            $errorHandler,
        );
    }

    public function testSignKidPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account', ['foo' => 'bar']);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload'], true));
    }

    public function testSignKidPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account', []);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload'], true));
    }

    public function testSignKidPayloadWithNullPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload'], true));
    }

    public function testSignJwkPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo', ['foo' => 'bar']);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload'], true));
    }

    public function testSignJwkPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo', []);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload'], true));
    }

    public function testSignJwkPayloadWithNullPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload'], true));
    }

    public function testValidStringRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], 'foo')], false);
        $body = $client->request('GET', '/foo', ['foo' => 'bar'], false);
        $this->assertEquals('foo', $body);
    }

    public function testValidJsonRequest()
    {
        $client = $this->createMockedClient([new Response(200, [], json_encode(['test' => 'ok']))], false);
        $data = $client->request('GET', '/foo', ['foo' => 'bar'], true);
        $this->assertEquals(['test' => 'ok'], $data);
    }

    public function testInvalidJsonRequest()
    {
        $this->expectException('AcmePhp\Core\Exception\Protocol\ExpectedJsonException');
        $client = $this->createMockedClient([new Response(200, [], 'invalid json')], false);
        $client->request('GET', '/foo', ['foo' => 'bar'], true);
    }

    public function testCreateExternalAccountPayload(): void
    {
        $client = new SecureHttpClient(
            (new KeyPairGenerator())->generateKeyPair(),
            new Client(),
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            new ServerErrorHandler(),
        );

        $payload = $client->createExternalAccountPayload(new ExternalAccount('id', str_repeat('hmacKey', '100')), 'bar');

        $this->assertArrayHasKey('protected', $payload);
        $this->assertSame('eyJhbGciOiJIUzI1NiIsImtpZCI6ImlkIiwidXJsIjoiYmFyIn0', $payload['protected']);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertStringStartsWith('ey', $payload['payload']);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertNotEmpty($payload['signature']);
    }

    public function testRequestPayload()
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
            $this->getMockBuilder(ServerErrorHandler::class)->getMock(),
        );

        $client->request('POST', '/acme/new-reg', $client->signJwkPayload('/acme/new-reg', ['contact' => 'foo@bar.com']), true);

        // Check request object
        $this->assertCount(1, $container);

        /** @var RequestInterface $request */
        $request = $container[0]['request'];

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/acme/new-reg', ($request->getUri() instanceof Uri) ? $request->getUri()->getPath() : $request->getUri());

        $payload = json_decode($request->getBody()->getContents(), true, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertEquals('Zm9vYmFy', $payload['signature']);
    }
}
