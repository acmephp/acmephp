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

use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Server\BadCsrServerException;
use AcmePhp\Core\Exception\Server\BadNonceServerException;
use AcmePhp\Core\Exception\Server\CaaServerException;
use AcmePhp\Core\Exception\Server\ConnectionServerException;
use AcmePhp\Core\Exception\Server\DnsServerException;
use AcmePhp\Core\Exception\Server\IncorrectResponseServerException;
use AcmePhp\Core\Exception\Server\InternalServerException;
use AcmePhp\Core\Exception\Server\InvalidContactServerException;
use AcmePhp\Core\Exception\Server\InvalidEmailServerException;
use AcmePhp\Core\Exception\Server\MalformedServerException;
use AcmePhp\Core\Exception\Server\OrderNotReadyServerException;
use AcmePhp\Core\Exception\Server\RateLimitedServerException;
use AcmePhp\Core\Exception\Server\RejectedIdentifierServerException;
use AcmePhp\Core\Exception\Server\TlsServerException;
use AcmePhp\Core\Exception\Server\UnauthorizedServerException;
use AcmePhp\Core\Exception\Server\UnknownHostServerException;
use AcmePhp\Core\Exception\Server\UnsupportedContactServerException;
use AcmePhp\Core\Exception\Server\UnsupportedIdentifierServerException;
use AcmePhp\Core\Exception\Server\UserActionRequiredServerException;
use AcmePhp\Core\Http\ServerErrorHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ServerErrorHandlerTest extends TestCase
{
    public function getErrorTypes()
    {
        return [
            ['badCSR', BadCsrServerException::class],
            ['badNonce', BadNonceServerException::class],
            ['caa', CaaServerException::class],
            ['connection', ConnectionServerException::class],
            ['dns', DnsServerException::class],
            ['incorrectResponse', IncorrectResponseServerException::class],
            ['invalidContact', InvalidContactServerException::class],
            ['invalidEmail', InvalidEmailServerException::class],
            ['malformed', MalformedServerException::class],
            ['orderNotReady', OrderNotReadyServerException::class],
            ['rateLimited', RateLimitedServerException::class],
            ['rejectedIdentifier', RejectedIdentifierServerException::class],
            ['serverInternal', InternalServerException::class],
            ['tls', TlsServerException::class],
            ['unauthorized', UnauthorizedServerException::class],
            ['unknownHost', UnknownHostServerException::class],
            ['unsupportedContact', UnsupportedContactServerException::class],
            ['unsupportedIdentifier', UnsupportedIdentifierServerException::class],
            ['userActionRequired', UserActionRequiredServerException::class],
        ];
    }

    /**
     * @dataProvider getErrorTypes
     */
    public function testAcmeExceptionThrown($type, $exceptionClass)
    {
        $errorHandler = new ServerErrorHandler();

        $response = new Response(500, [], json_encode([
            'type' => 'urn:acme:error:'.$type,
            'detail' => $exceptionClass.'Detail',
        ]));

        $exception = $errorHandler->createAcmeExceptionForResponse(new Request('GET', '/foo/bar'), $response);

        $this->assertInstanceOf($exceptionClass, $exception);
        $this->assertStringContainsString($type, $exception->getMessage());
        $this->assertStringContainsString($exceptionClass.'Detail', $exception->getMessage());
        $this->assertStringContainsString('/foo/bar', $exception->getMessage());
    }

    public function testDefaultExceptionThrownWithInvalidJson()
    {
        $errorHandler = new ServerErrorHandler();

        $exception = $errorHandler->createAcmeExceptionForResponse(
            new Request('GET', '/foo/bar'),
            new Response(500, [], 'Invalid JSON')
        );

        $this->assertInstanceOf(AcmeCoreServerException::class, $exception);
        $this->assertStringContainsString('non-ACME', $exception->getMessage());
        $this->assertStringContainsString('/foo/bar', $exception->getMessage());
        $this->assertStringContainsString('Invalid JSON', $exception->getMessage());
    }

    public function testDefaultExceptionThrownNonAcmeJson()
    {
        $errorHandler = new ServerErrorHandler();

        $exception = $errorHandler->createAcmeExceptionForResponse(
            new Request('GET', '/foo/bar'),
            new Response(500, [], json_encode(['not' => 'acme']))
        );

        $this->assertInstanceOf(AcmeCoreServerException::class, $exception);
        $this->assertStringContainsString('non-ACME', $exception->getMessage());
        $this->assertStringContainsString('/foo/bar', $exception->getMessage());
        $this->assertStringContainsString('"not":"acme"', $exception->getMessage());
    }
}
