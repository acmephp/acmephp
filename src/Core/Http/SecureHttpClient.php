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

use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\ExpectedJsonException;
use AcmePhp\Core\Exception\Server\BadNonceServerException;
use AcmePhp\Core\Util\JsonDecoder;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle HTTP client wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SecureHttpClient
{
    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Base64SafeEncoder
     */
    private $base64Encoder;

    /**
     * @var KeyParser
     */
    private $keyParser;

    /**
     * @var DataSigner
     */
    private $dataSigner;

    /**
     * @var ServerErrorHandler
     */
    private $errorHandler;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @var string
     */
    private $nonceEndpoint;

    /**
     * @param KeyPair            $accountKeyPair
     * @param ClientInterface    $httpClient
     * @param Base64SafeEncoder  $base64Encoder
     * @param KeyParser          $keyParser
     * @param DataSigner         $dataSigner
     * @param ServerErrorHandler $errorHandler
     */
    public function __construct(
        KeyPair $accountKeyPair,
        ClientInterface $httpClient,
        Base64SafeEncoder $base64Encoder,
        KeyParser $keyParser,
        DataSigner $dataSigner,
        ServerErrorHandler $errorHandler
    ) {
        $this->accountKeyPair = $accountKeyPair;
        $this->httpClient = $httpClient;
        $this->base64Encoder = $base64Encoder;
        $this->keyParser = $keyParser;
        $this->dataSigner = $dataSigner;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     * @throws AcmeCoreClientException when an error occured during response parsing
     *
     * @return array|string Array of parsed JSON if $returnJson = true, string otherwise
     */
    public function signedRequest($method, $endpoint, array $payload = [], $returnJson = true)
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();

        $protected = [
            'alg' => 'RS256',
            'jwk' => $this->getJWK(),
            'nonce' => $this->getNonce($endpoint),
            'url' => $endpoint,
        ];

        $protected = $this->base64Encoder->encode(json_encode($protected));
        $payload = $this->base64Encoder->encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->base64Encoder->encode($this->dataSigner->signData($protected.'.'.$payload, $privateKey));

        $payload = [
            'protected' => $protected,
            'payload' => $payload,
            'signature' => $signature,
        ];

        try {
            return $this->unsignedRequest($method, $endpoint, $payload, $returnJson);
        } catch (BadNonceServerException $e) {
            return $this->unsignedRequest($method, $endpoint, $payload, $returnJson);
        }
    }

    public function getJWK()
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();
        $parsedKey = $this->keyParser->parse($privateKey);

        return [
            // this order matter
            'e' => $this->base64Encoder->encode($parsedKey->getDetail('e')),
            'kty' => 'RSA',
            'n' => $this->base64Encoder->encode($parsedKey->getDetail('n')),
        ];
    }

    public function getJWKThumbprint()
    {
        return hash('sha256', json_encode($this->getJWK()), true);
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param string $account
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     * @throws AcmeCoreClientException when an error occured during response parsing
     *
     * @return array|string Array of parsed JSON if $returnJson = true, string otherwise
     */
    public function signedKidRequest($method, $endpoint, $account, array $payload = [], $returnJson = true)
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();

        $protected = [
            'alg' => 'RS256',
            'kid' => $account,
            'nonce' => $this->getNonce(),
            'url' => $endpoint,
        ];

        $protected = $this->base64Encoder->encode(json_encode($protected));
        if ($payload === []) {
            $payload = $this->base64Encoder->encode('{}');
        } else {
            $payload = $this->base64Encoder->encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        }
        $signature = $this->base64Encoder->encode($this->dataSigner->signData($protected.'.'.$payload, $privateKey));

        $payload = [
            'protected' => $protected,
            'payload' => $payload,
            'signature' => $signature,
        ];

        try {
            return $this->unsignedRequest($method, $endpoint, $payload, $returnJson);
        } catch (BadNonceServerException $e) {
            return $this->unsignedRequest($method, $endpoint, $payload, $returnJson);
        }
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $data
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     * @throws AcmeCoreClientException when an error occured during response parsing
     * @throws ExpectedJsonException   when $returnJson = true and the response is not valid JSON
     *
     * @return array|string Array of parsed JSON if $returnJson = true, string otherwise
     */
    public function unsignedRequest($method, $endpoint, array $data = null, $returnJson = true)
    {
        $request = $this->createRequest($method, $endpoint, $data);

        try {
            $this->lastResponse = $this->httpClient->send($request);
        } catch (\Exception $exception) {
            $this->handleClientException($request, $exception);
        }

        $body = \GuzzleHttp\Psr7\copy_to_string($this->lastResponse->getBody());

        if (!$returnJson) {
            return $body;
        }

        try {
            if ('' === $body) {
                return;
            }

            $data = JsonDecoder::decode($body, true);
        } catch (\InvalidArgumentException $exception) {
            throw new ExpectedJsonException(
                sprintf(
                    'ACME client excepted valid JSON as a response to request "%s %s" (given: "%s")',
                    $request->getMethod(),
                    $request->getUri(),
                    ServerErrorHandler::getResponseBodySummary($this->lastResponse)
                ),
                $exception
            );
        }

        return $data;
    }

    /**
     * @param KeyPair $keyPair
     */
    public function setAccountKeyPair(KeyPair $keyPair)
    {
        $this->accountKeyPair = $keyPair;
    }

    /**
     * @return int
     */
    public function getLastCode()
    {
        return $this->lastResponse->getStatusCode();
    }

    /**
     * @return string
     */
    public function getLastLocation()
    {
        return $this->lastResponse->getHeaderLine('Location');
    }

    /**
     * @return array
     */
    public function getLastLinks()
    {
        return \GuzzleHttp\Psr7\parse_header($this->lastResponse->getHeader('Link'));
    }

    /**
     * @return KeyPair
     */
    public function getAccountKeyPair()
    {
        return $this->accountKeyPair;
    }

    /**
     * @return KeyParser
     */
    public function getKeyParser()
    {
        return $this->keyParser;
    }

    /**
     * @return DataSigner
     */
    public function getDataSigner()
    {
        return $this->dataSigner;
    }

    /**
     * @param string $endpoint
     */
    public function setNonceEndpoint($endpoint)
    {
        $this->nonceEndpoint = $endpoint;
    }

    /**
     * @return Base64SafeEncoder
     */
    public function getBase64Encoder()
    {
        return $this->base64Encoder;
    }

    private function createRequest($method, $endpoint, $data)
    {
        $request = new Request($method, $endpoint);
        $request = $request->withHeader('Accept', 'application/json,application/jose+json,');

        if ('POST' === $method && is_array($data)) {
            $request = $request->withHeader('Content-Type', 'application/jose+json');
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for(json_encode($data)));
        }

        return $request;
    }

    private function handleClientException(Request $request, \Exception $exception)
    {
        if ($exception instanceof RequestException && $exception->getResponse() instanceof ResponseInterface) {
            $this->lastResponse = $exception->getResponse();

            throw $this->errorHandler->createAcmeExceptionForResponse($request, $this->lastResponse, $exception);
        }

        throw new AcmeCoreClientException(
            sprintf('An error occured during request "%s %s"', $request->getMethod(), $request->getUri()),
            $exception
        );
    }

    private function getNonce()
    {
        if ($this->lastResponse && $this->lastResponse->hasHeader('Replay-Nonce')) {
            return $this->lastResponse->getHeaderLine('Replay-Nonce');
        }

        if (null !== $this->nonceEndpoint) {
            $this->unsignedRequest('HEAD', $this->nonceEndpoint, null, false);
            if ($this->lastResponse->hasHeader('Replay-Nonce')) {
                return $this->lastResponse->getHeaderLine('Replay-Nonce');
            }
        }
    }
}
