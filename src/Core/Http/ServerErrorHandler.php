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
use AcmePhp\Core\Exception\Server\ConnectionServerException;
use AcmePhp\Core\Exception\Server\InternalServerException;
use AcmePhp\Core\Exception\Server\InvalidEmailServerException;
use AcmePhp\Core\Exception\Server\MalformedServerException;
use AcmePhp\Core\Exception\Server\RateLimitedServerException;
use AcmePhp\Core\Exception\Server\TlsServerException;
use AcmePhp\Core\Exception\Server\UnauthorizedServerException;
use AcmePhp\Core\Exception\Server\UnknownHostServerException;
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
        'connection' => ConnectionServerException::class,
        'serverInternal' => InternalServerException::class,
        'invalidEmail' => InvalidEmailServerException::class,
        'malformed' => MalformedServerException::class,
        'rateLimited' => RateLimitedServerException::class,
        'tls' => TlsServerException::class,
        'unauthorized' => UnauthorizedServerException::class,
        'unknownHost' => UnknownHostServerException::class,
    ];

    /**
     * Get a response summary (useful for exceptions).
     * Use Guzzle method if available (Guzzle 6.1.1+).
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function getResponseBodySummary(ResponseInterface $response)
    {
        if (method_exists(RequestException::class, 'getResponseBodySummary')) {
            return RequestException::getResponseBodySummary($response);
        }

        $body = \GuzzleHttp\Psr7\copy_to_string($response->getBody());

        if (\strlen($body) > 120) {
            return substr($body, 0, 120).' (truncated...)';
        }

        return $body;
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param \Exception|null   $previous
     *
     * @return AcmeCoreServerException
     */
    public function createAcmeExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ) {
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

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param \Exception|null   $previous
     *
     * @return AcmeCoreServerException
     */
    private function createDefaultExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ) {
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
