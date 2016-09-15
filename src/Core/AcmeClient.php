<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

use AcmePhp\Core\ChallengeSolver\SolverInterface;
use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestFailedException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestTimedOutException;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\ResourcesDirectory;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use Webmozart\Assert\Assert;

/**
 * ACME protocol client implementation.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient implements AcmeClientInterface
{
    /**
     * @var SecureHttpClient
     */
    private $httpClient;

    /**
     * @var CertificateRequestSigner
     */
    private $csrSigner;

    /**
     * @var string
     */
    private $directoryUrl;

    /**
     * @var ResourcesDirectory
     */
    private $directory;

    /**
     * @param SecureHttpClient              $httpClient
     * @param string                        $directoryUrl
     * @param CertificateRequestSigner|null $csrSigner
     */
    public function __construct(SecureHttpClient $httpClient, $directoryUrl, CertificateRequestSigner $csrSigner = null)
    {
        $this->httpClient = $httpClient;
        $this->directoryUrl = $directoryUrl;
        $this->csrSigner = $csrSigner ?: new CertificateRequestSigner();
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($agreement = null, $email = null)
    {
        Assert::nullOrString($agreement, 'registerAccount::$agreement expected a string or null. Got: %s');
        Assert::nullOrString($email, 'registerAccount::$email expected a string or null. Got: %s');

        $payload = [];
        $payload['resource'] = ResourcesDirectory::NEW_REGISTRATION;
        $payload['agreement'] = $agreement;

        if (is_string($email)) {
            $payload['contact'] = ['mailto:'.$email];
        }

        $response = (array) $this->requestResource('POST', ResourcesDirectory::NEW_REGISTRATION, $payload);
        $links = $this->httpClient->getLastLinks();
        foreach ($links as $link) {
            if ('terms-of-service' === $link['rel']) {
                $agreement = substr($link[0], 1, -1);
                $payload = [];
                $payload['resource'] = ResourcesDirectory::REGISTRATION;
                $payload['agreement'] = $agreement;

                $this->httpClient->signedRequest(
                    'POST',
                    $this->httpClient->getLastLocation(),
                    $payload,
                    true
                );

                break;
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function requestAuthorization(SolverInterface $solver, $domain)
    {
        Assert::string($domain, 'requestAuthorization::$domain expected a string. Got: %s');

        $payload = [
            'resource'   => ResourcesDirectory::NEW_AUTHORIZATION,
            'identifier' => [
                'type'  => 'dns',
                'value' => $domain,
            ],
        ];

        $response = $this->requestResource('POST', ResourcesDirectory::NEW_AUTHORIZATION, $payload);

        if (!isset($response['challenges']) || !$response['challenges']) {
            throw new ChallengeNotSupportedException();
        }

        $base64encoder = $this->httpClient->getBase64Encoder();
        $keyParser = $this->httpClient->getKeyParser();
        $accountKeyPair = $this->httpClient->getAccountKeyPair();

        $parsedKey = $keyParser->parse($accountKeyPair->getPrivateKey());

        $header = json_encode([
            // This order matters
            'e'   => $base64encoder->encode($parsedKey->getDetail('e')),
            'kty' => 'RSA',
            'n'   => $base64encoder->encode($parsedKey->getDetail('n')),
        ]);

        $encodedHeader = $base64encoder->encode(hash('sha256', $header, true));

        foreach ($response['challenges'] as $challenge) {
            if (!$solver->supports($challenge['type'])) {
                continue;
            }

            $authorizationChallenge = new AuthorizationChallenge(
                $domain,
                $challenge['type'],
                $challenge['uri'],
                $challenge['token'],
                $challenge['token'].'.'.$encodedHeader
            );

            $solver->initialize($authorizationChallenge);

            return $authorizationChallenge;
        }

        throw new ChallengeNotSupportedException();
    }

    /**
     * {@inheritdoc}
     */
    public function challengeAuthorization(SolverInterface $solver, AuthorizationChallenge $challenge, $timeout = 180)
    {
        Assert::integer($timeout, 'challengeAuthorization::$timeout expected an integer. Got: %s');

        if (!$solver->supports($challenge->getType())) {
            throw new ChallengeNotSupportedException();
        }

        $payload = [
            'resource'         => ResourcesDirectory::CHALLENGE,
            'type'             => $challenge->getType(),
            'keyAuthorization' => $challenge->getPayload(),
            'token'            => $challenge->getToken(),
        ];

        if (!$this->directory) {
            $this->initializeDirectory();
        }

        $response = (array) $this->httpClient->signedRequest('POST', $challenge->getUrl(), $payload);
        // Waiting loop
        $endTime = time() + $timeout;

        while (time() <= $endTime && (!isset($response['status']) || 'pending' === $response['status'])) {
            sleep(1);
            $response = (array) $this->httpClient->signedRequest('GET', $challenge->getUrl());
        }

        if (!isset($response['status']) || 'valid' !== $response['status']) {
            throw new ChallengeFailedException($response);
        } elseif ('pending' === $response['status']) {
            throw new ChallengeTimedOutException($response);
        }


        $solver->cleanup($challenge);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, CertificateRequest $csr, $timeout = 180)
    {
        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        $humanText = ['-----BEGIN CERTIFICATE REQUEST-----', '-----END CERTIFICATE REQUEST-----'];

        $csrContent = $this->csrSigner->signCertificateRequest($csr);
        $csrContent = trim(str_replace($humanText, '', $csrContent));
        $csrContent = trim($this->httpClient->getBase64Encoder()->encode(base64_decode($csrContent)));

        $response = $this->requestResource('POST', ResourcesDirectory::NEW_CERTIFICATE, [
            'resource' => ResourcesDirectory::NEW_CERTIFICATE,
            'csr'      => $csrContent,
        ], false);

        // If the CA has not yet issued the certificate, the body of this response will be empty
        if (strlen(trim($response)) < 10) { // 10 to avoid false results
            $location = $this->httpClient->getLastLocation();

            // Waiting loop
            $endTime = time() + $timeout;

            while (time() <= $endTime) {
                $response = $this->httpClient->unsignedRequest('GET', $location, null, false);

                if (200 === $this->httpClient->getLastCode()) {
                    break;
                }

                if (202 !== $this->httpClient->getLastCode()) {
                    throw new CertificateRequestFailedException($response);
                }

                sleep(1);
            }

            if (202 === $this->httpClient->getLastCode()) {
                throw new CertificateRequestTimedOutException($response);
            }
        }

        // Find issuers certificate
        $links = $this->httpClient->getLastLinks();
        $certificatesChain = null;

        foreach ($links as $link) {
            if (!isset($link['rel']) || 'up' !== $link['rel']) {
                continue;
            }

            $location = trim($link[0], '<>');
            $certificate = $this->httpClient->unsignedRequest('GET', $location, null, false);

            if (strlen(trim($certificate)) > 10) {
                $pem = chunk_split(base64_encode($certificate), 64, "\n");
                $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";

                $certificatesChain = new Certificate($pem, $certificatesChain);
            }
        }

        // Domain certificate
        $pem = chunk_split(base64_encode($response), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";

        return new CertificateResponse($csr, new Certificate($pem, $certificatesChain));
    }

    /**
     * Request a resource (URL is found using ACME server directory).
     *
     * @param string $method
     * @param string $resource
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException When the ACME server returns an error HTTP status code.
     * @throws AcmeCoreClientException When an error occured during response parsing.
     *
     * @return array|string
     */
    protected function requestResource($method, $resource, array $payload, $returnJson = true)
    {
        if (!$this->directory) {
            $this->initializeDirectory();
        }

        return $this->httpClient->signedRequest(
            $method,
            $this->directory->getResourceUrl($resource),
            $payload,
            $returnJson
        );
    }

    /**
     * Initialize the server resources directory.
     */
    private function initializeDirectory()
    {
        $this->directory = new ResourcesDirectory(
            $this->httpClient->unsignedRequest('GET', $this->directoryUrl, null, true)
        );
    }
}
