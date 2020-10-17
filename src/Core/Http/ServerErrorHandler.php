<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Http;

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
use AcmePhp\Core\Util\JsonDecoder;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Create appropriate exception for given server response.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ServerErrorHandler
{
    private static $exceptions = [
        'badCSR' => BadCsrServerException::class,
        'badNonce' => BadNonceServerException::class,
        'caa' => CaaServerException::class,
        'connection' => ConnectionServerException::class,
        'dns' => DnsServerException::class,
        'incorrectResponse' => IncorrectResponseServerException::class,
        'invalidContact' => InvalidContactServerException::class,
        'invalidEmail' => InvalidEmailServerException::class,
        'malformed' => MalformedServerException::class,
        'orderNotReady' => OrderNotReadyServerException::class,
        'rateLimited' => RateLimitedServerException::class,
        'rejectedIdentifier' => RejectedIdentifierServerException::class,
        'serverInternal' => InternalServerException::class,
        'tls' => TlsServerException::class,
        'unauthorized' => UnauthorizedServerException::class,
        'unknownHost' => UnknownHostServerException::class,
        'unsupportedContact' => UnsupportedContactServerException::class,
        'unsupportedIdentifier' => UnsupportedIdentifierServerException::class,
        'userActionRequired' => UserActionRequiredServerException::class,
    ];

    /**
     * Get a response summary (useful for exceptions).
     * Use Guzzle method if available (Guzzle 6.1.1+).
     */
    public static function getResponseBodySummary(ResponseInterface $response): string
    {
        // Rewind the stream if possible to allow re-reading for the summary.
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        if (method_exists(RequestException::class, 'getResponseBodySummary')) {
            return RequestException::getResponseBodySummary($response);
        }

        $body = \GuzzleHttp\Psr7\copy_to_string($response->getBody());

        if (\strlen($body) > 120) {
            return substr($body, 0, 120).' (truncated...)';
        }

        return $body;
    }

    public function createAcmeExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ): AcmeCoreServerException {
        $body = \GuzzleHttp\Psr7\copy_to_string($response->getBody());

        try {
            $data = JsonDecoder::decode($body, true);
        } catch (\InvalidArgumentException $e) {
            $data = null;
        }

        if (!$data || !isset($data['type'], $data['detail'])) {
            // Not JSON: not an ACME error response
            return $this->createDefaultExceptionForResponse($request, $response, $previous);
        }

        $type = preg_replace('/^urn:(ietf:params:)?acme:error:/i', '', $data['type']);

        if (!isset(self::$exceptions[$type])) {
            // Unknown type: not an ACME error response
            return $this->createDefaultExceptionForResponse($request, $response, $previous);
        }

        $exceptionClass = self::$exceptions[$type];

        return new $exceptionClass(
            $request,
            sprintf('%s (on request "%s %s")', $data['detail'], $request->getMethod(), $request->getUri()),
            $previous
        );
    }

    private function createDefaultExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ): AcmeCoreServerException {
        return new AcmeCoreServerException(
            $request,
            sprintf(
                'A non-ACME %s HTTP error occured on request "%s %s" (response body: "%s")',
                $response->getStatusCode(),
                $request->getMethod(),
                $request->getUri(),
                self::getResponseBodySummary($response)
            ),
            $previous
        );
    }
}
