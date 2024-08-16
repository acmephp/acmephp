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
use AcmePhp\Core\Protocol\ExternalAccount;
use AcmePhp\Core\Util\JsonDecoder;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
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

    public function __construct(
        KeyPair $accountKeyPair,
        ClientInterface $httpClient,
        Base64SafeEncoder $base64Encoder,
        KeyParser $keyParser,
        DataSigner $dataSigner,
        ServerErrorHandler $errorHandler,
    ) {
        $this->accountKeyPair = $accountKeyPair;
        $this->httpClient = $httpClient;
        $this->base64Encoder = $base64Encoder;
        $this->keyParser = $keyParser;
        $this->dataSigner = $dataSigner;
        $this->errorHandler = $errorHandler;
    }

    public function getJWK(): array
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();
        $parsedKey = $this->keyParser->parse($privateKey);

        switch ($parsedKey->getType()) {
            case OPENSSL_KEYTYPE_RSA:
                return array(
                    // this order matters
                    'e' => $this->base64Encoder->encode($parsedKey->getDetail('e')),
                    'kty' => 'RSA',
                    'n' => $this->base64Encoder->encode($parsedKey->getDetail('n')),
                );

            case OPENSSL_KEYTYPE_EC:
                return array(
                    // this order matters
                    'crv' => 'P-' . $parsedKey->getBits(),
                    'kty' => 'EC',
                    'x' => $this->base64Encoder->encode($parsedKey->getDetail('x')),
                    'y' => $this->base64Encoder->encode($parsedKey->getDetail('y')),
                );

            default:
                throw new AcmeCoreClientException('Private key type not supported');
        }
    }

    public function getJWKThumbprint(): string
    {
        return hash('sha256', json_encode($this->getJWK()), true);
    }

    /**
     * Generates a payload signed with account's KID.
     *
     * @param string|array|null $payload
     */
    public function signKidPayload(string $endpoint, string $account, $payload = null, bool $withNonce = true): array
    {
        $protected = array('alg' => $this->getAlg(), 'kid' => $account, 'url' => $endpoint);
        if ($withNonce) {
            $protected['nonce'] = $this->getNonce();
        }

        return $this->signPayload($protected, $payload);
    }

    /**
     * Generates a payload signed with JWK.
     *
     * @param string|array|null $payload
     */
    public function signJwkPayload(string $endpoint, $payload = null, bool $withNonce = true): array
    {
        $protected = array('alg' => $this->getAlg(), 'jwk' => $this->getJWK(), 'url' => $endpoint);
        if ($withNonce) {
            $protected['nonce'] = $this->getNonce();
        }

        return $this->signPayload($protected, $payload);
    }

    /**
     * Generates an External Account Binding payload signed with JWS.
     */
    public function createExternalAccountPayload(ExternalAccount $externalAccount, string $url): array
    {
        $signer = new Sha256();

        $protected = array(
            'alg' => method_exists($signer, 'algorithmId') ? $signer->algorithmId() : $signer->getAlgorithmId(),
            'kid' => $externalAccount->getId(),
            'url' => $url,
        );

        $encodedProtected = $this->base64Encoder->encode(json_encode($protected, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64Encoder->encode(json_encode($this->getJWK(), JSON_UNESCAPED_SLASHES));

        $hmacKey = $this->base64Encoder->decode($externalAccount->getHmacKey());
        $hmacKey = class_exists(InMemory::class) ? InMemory::plainText($hmacKey) : $hmacKey;

        $signature = $this->base64Encoder->encode($signer->sign($encodedProtected . '.' . $encodedPayload, $hmacKey));

        return array(
            'protected' => $encodedProtected,
            'payload' => $encodedPayload,
            'signature' => $signature,
        );
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol
     * and its content (optionally parsed as JSON).
     *
     * @return array|string Array of parsed JSON if $returnJson = true, string otherwise
     *
     * @throws AcmeCoreClientException when an error occured during response parsing
     * @throws ExpectedJsonException   when $returnJson = true and the response is not valid JSON
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     */
    public function request(string $method, string $endpoint, array $data = array(), bool $returnJson = true)
    {
        $response = $this->rawRequest($method, $endpoint, $data, $returnJson);
        $body = Utils::copyToString($response->getBody());

        if (!$returnJson) {
            return $body;
        }

        try {
            if ('' === $body) {
                throw new \InvalidArgumentException('Empty body received.');
            }

            $data = JsonDecoder::decode($body, true);
        } catch (\InvalidArgumentException $exception) {
            throw new ExpectedJsonException(sprintf('ACME client expected valid JSON as a response to request "%s %s" (given: "%s")', $method, $endpoint, ServerErrorHandler::getResponseBodySummary($response)), $exception);
        }

        return $data;
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol and return the response object.
     *
     * @throws AcmeCoreClientException when an error occured during response parsing
     * @throws ExpectedJsonException   when $returnJson = true and the response is not valid JSON
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     */
    public function rawRequest(string $method, string $endpoint, array $data = array(), bool $acceptJson = false): ResponseInterface
    {
        $call = function () use ($method, $endpoint, $data, $acceptJson) {
            $request = $this->createRequest($method, $endpoint, $data, $acceptJson);
            try {
                $this->lastResponse = $this->httpClient->send($request);
            } catch (\Exception $exception) {
                $this->handleClientException($request, $exception);
            }

            return $request;
        };

        try {
            $call();
        } catch (BadNonceServerException $e) {
            $call();
        }

        return $this->lastResponse;
    }

    public function setAccountKeyPair(KeyPair $keyPair)
    {
        $this->accountKeyPair = $keyPair;
    }

    public function getLastCode(): int
    {
        return $this->lastResponse->getStatusCode();
    }

    public function getLastLocation(): string
    {
        return $this->lastResponse->getHeaderLine('Location');
    }

    public function getLastLinks(): array
    {
        return Header::parse($this->lastResponse->getHeader('Link'));
    }

    public function getAccountKeyPair(): KeyPair
    {
        return $this->accountKeyPair;
    }

    public function getKeyParser(): KeyParser
    {
        return $this->keyParser;
    }

    public function getDataSigner(): DataSigner
    {
        return $this->dataSigner;
    }

    public function setNonceEndpoint(string $endpoint)
    {
        $this->nonceEndpoint = $endpoint;
    }

    public function getBase64Encoder(): Base64SafeEncoder
    {
        return $this->base64Encoder;
    }

    /**
     * Sign the given Payload.
     */
    private function signPayload(array $protected, ?array $payload = null): array
    {
        if (!isset($protected['alg'])) {
            throw new \InvalidArgumentException('The property "alg" is required in the protected array');
        }

        $alg = $protected['alg'];

        $privateKey = $this->accountKeyPair->getPrivateKey();
        [$algorithm, $format] = $this->extractSignOptionFromJWSAlg($alg);

        $encodedProtected = $this->base64Encoder->encode(json_encode($protected, JSON_UNESCAPED_SLASHES));

        if (null === $payload) {
            $encodedPayload = '';
        } elseif (array() === $payload) {
            $encodedPayload = $this->base64Encoder->encode('{}');
        } else {
            $encodedPayload = $this->base64Encoder->encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        $signature = $this->base64Encoder->encode(
            $this->dataSigner->signData($encodedProtected . '.' . $encodedPayload, $privateKey, $algorithm, $format),
        );

        return array(
            'protected' => $encodedProtected,
            'payload' => $encodedPayload,
            'signature' => $signature,
        );
    }

    private function createRequest($method, $endpoint, $data, $acceptJson)
    {
        $request = new Request($method, $endpoint);

        if ($acceptJson) {
            $request = $request->withHeader('Accept', 'application/json,application/jose+json,');
        } else {
            $request = $request->withHeader('Accept', '*/*');
        }

        if ('POST' === $method && \is_array($data)) {
            $request = $request->withHeader('Content-Type', 'application/jose+json');
            $request = $request->withBody(Utils::streamFor(json_encode($data)));
        }

        return $request;
    }

    private function handleClientException(Request $request, \Exception $exception)
    {
        if ($exception instanceof RequestException && $exception->getResponse() instanceof ResponseInterface) {
            $this->lastResponse = $exception->getResponse();

            throw $this->errorHandler->createAcmeExceptionForResponse($request, $this->lastResponse, $exception);
        }

        throw new AcmeCoreClientException(sprintf('An error occured during request "%s %s"', $request->getMethod(), $request->getUri()), $exception);
    }

    private function getNonce(): ?string
    {
        if ($this->lastResponse && $this->lastResponse->hasHeader('Replay-Nonce')) {
            return $this->lastResponse->getHeaderLine('Replay-Nonce');
        }

        if (null !== $this->nonceEndpoint) {
            $this->request('HEAD', $this->nonceEndpoint, array(), false);

            if ($this->lastResponse->hasHeader('Replay-Nonce')) {
                return $this->lastResponse->getHeaderLine('Replay-Nonce');
            }
        }

        return null;
    }

    private function getAlg(): string
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();
        $parsedKey = $this->keyParser->parse($privateKey);

        switch ($parsedKey->getType()) {
            case OPENSSL_KEYTYPE_RSA:
                return 'RS256';

            case OPENSSL_KEYTYPE_EC:
                switch ($parsedKey->getBits()) {
                    case 256:
                    case 384:
                        return 'ES' . $parsedKey->getBits();
                    case 521:
                        return 'ES512';
                }

                // no break to let the default case
            default:
                throw new AcmeCoreClientException('Private key type is not supported');
        }
    }

    private function extractSignOptionFromJWSAlg($alg): array
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', $alg, $match)) {
            throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        if (!\defined('OPENSSL_ALGO_SHA' . $match[2])) {
            throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        $algorithm = \constant('OPENSSL_ALGO_SHA' . $match[2]);

        switch ($match[1]) {
            case 'RS':
                $format = DataSigner::FORMAT_DER;
                break;

            case 'ES':
                $format = DataSigner::FORMAT_ECDSA;
                break;

            default:
                throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        return array($algorithm, $format);
    }
}
