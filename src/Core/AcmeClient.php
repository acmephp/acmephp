<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestFailedException;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
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
class AcmeClient implements AcmeClientV2Interface
{
    /**
     * @var SecureHttpClient
     */
    private $uninitializedHttpClient;

    /**
     * @var SecureHttpClient
     */
    private $initializedHttpClient;

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
     * @var ResourcesAccount
     */
    private $account;

    /**
     * @param SecureHttpClient              $httpClient
     * @param string                        $directoryUrl
     * @param CertificateRequestSigner|null $csrSigner
     */
    public function __construct(SecureHttpClient $httpClient, $directoryUrl, CertificateRequestSigner $csrSigner = null)
    {
        $this->uninitializedHttpClient = $httpClient;
        $this->directoryUrl = $directoryUrl;
        $this->csrSigner = $csrSigner ?: new CertificateRequestSigner();
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        if (!$this->initializedHttpClient) {
            $this->initializedHttpClient = $this->uninitializedHttpClient;

            $this->initializedHttpClient->setNonceEndpoint($this->getResourceUrl(ResourcesDirectory::NEW_NONCE));
        }

        return $this->initializedHttpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($agreement = null, $email = null)
    {
        Assert::nullOrString($agreement, 'registerAccount::$agreement expected a string or null. Got: %s');
        Assert::nullOrString($email, 'registerAccount::$email expected a string or null. Got: %s');

        $payload = [
            'termsOfServiceAgreed' => true,
            'contact' => [],
        ];

        if (is_string($email)) {
            $payload['contact'][] = 'mailto:'.$email;
        }

        $this->requestResource('POST', ResourcesDirectory::NEW_ACCOUNT, $payload);

        return $this->getHttpClient()->signedKidRequest('POST', $this->getResourceAccount(), $this->getResourceAccount());
    }

    /**
     * {@inheritdoc}
     */
    public function requestAuthorization($domain)
    {
        $order = $this->requestOrder([$domain]);

        try {
            return $order->getAuthorizationChallenges($domain);
        } catch (AcmeCoreClientException $e) {
            throw new ChallengeNotSupportedException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requestOrder(array $domains)
    {
        Assert::allStringNotEmpty($domains, 'requestOrder::$domains expected a list of strings. Got: %s');

        $payload = [
            'identifiers' => array_map(
                function ($domain) {
                    return [
                        'type' => 'dns',
                        'value' => $domain,
                    ];
                },
                $domains
            ),
        ];

        $response = $this->getHttpClient()->signedKidRequest('POST', $this->getResourceUrl(ResourcesDirectory::NEW_ORDER), $this->getResourceAccount(), $payload);
        if (!isset($response['authorizations']) || !$response['authorizations']) {
            throw new ChallengeNotSupportedException();
        }

        $orderEndpoint = $this->getHttpClient()->getLastLocation();
        foreach ($response['authorizations'] as $authorizationEndpoint) {
            $authorizationsResponse = $this->getHttpClient()->unsignedRequest('GET', $authorizationEndpoint, null, true);
            $domain = (empty($authorizationsResponse['wildcard']) ? '' : '*.').$authorizationsResponse['identifier']['value'];
            foreach ($authorizationsResponse['challenges'] as $challenge) {
                $authorizationsChallenges[$domain][] = $this->createAuthorizationChallenge($authorizationsResponse['identifier']['value'], $challenge);
            }
        }

        return new CertificateOrder($authorizationsChallenges, $orderEndpoint);
    }

    /**
     * {@inheritdoc}
     */
    public function reloadAuthorization(AuthorizationChallenge $challenge)
    {
        $response = (array) $this->getHttpClient()->unsignedRequest('GET', $challenge->getUrl());

        return $this->createAuthorizationChallenge($challenge->getDomain(), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function challengeAuthorization(AuthorizationChallenge $challenge, $timeout = 180)
    {
        Assert::integer($timeout, 'challengeAuthorization::$timeout expected an integer. Got: %s');

        $endTime = time() + $timeout;
        $response = (array) $this->getHttpClient()->unsignedRequest('GET', $challenge->getUrl());
        if ('pending' === $response['status']) {
            $response = (array) $this->getHttpClient()->signedKidRequest('POST', $challenge->getUrl(), $this->getResourceAccount(), []);
        }

        // Waiting loop
        while (time() <= $endTime && (!isset($response['status']) || 'pending' === $response['status'])) {
            sleep(1);
            $response = (array) $this->getHttpClient()->unsignedRequest('GET', $challenge->getUrl());
        }

        if (!isset($response['status']) || 'valid' !== $response['status']) {
            throw new ChallengeFailedException($response);
        }
        if ('pending' === $response['status']) {
            throw new ChallengeTimedOutException($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, CertificateRequest $csr, $timeout = 180)
    {
        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        $order = $this->requestOrder(array_unique(array_merge([$domain], $csr->getDistinguishedName()->getSubjectAlternativeNames())));

        return $this->finalizeOrder($order, $csr, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeOrder(CertificateOrder $order, CertificateRequest $csr, $timeout = 180)
    {
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        $endTime = time() + $timeout;
        $response = $this->getHttpClient()->signedKidRequest('GET', $order->getOrderEndpoint(), $this->getResourceAccount());
        if ('pending' === $response['status']) {
            $humanText = ['-----BEGIN CERTIFICATE REQUEST-----', '-----END CERTIFICATE REQUEST-----'];

            $csrContent = $this->csrSigner->signCertificateRequest($csr);
            $csrContent = trim(str_replace($humanText, '', $csrContent));
            $csrContent = trim($this->getHttpClient()->getBase64Encoder()->encode(base64_decode($csrContent)));

            $response = $this->getHttpClient()->signedKidRequest('POST', $response['finalize'], $this->getResourceAccount(), [
                'csr' => $csrContent,
            ]);
        }

        // Waiting loop
        while (time() <= $endTime && (!isset($response['status']) || 'pending' === $response['status'])) {
            sleep(1);
            $response = $this->getHttpClient()->signedKidRequest('GET', $order->getOrderEndpoint(), $this->getResourceAccount());
        }

        if ('valid' !== $response['status']) {
            throw new CertificateRequestFailedException('The order has not been validated');
        }

        $response = $this->getHttpClient()->unsignedRequest('GET', $response['certificate'], null, false);
        $certificatesChain = null;
        foreach (array_reverse(explode("\n\n", $response)) as $pem) {
            $certificatesChain = new Certificate($pem, $certificatesChain);
        }

        return new CertificateResponse($csr, $certificatesChain);
    }

    /**
     * Find a resource URL.
     *
     * @param string $resource
     *
     * @return string
     */
    public function getResourceUrl($resource)
    {
        if (!$this->directory) {
            $this->directory = new ResourcesDirectory(
                $this->getHttpClient()->unsignedRequest('GET', $this->directoryUrl, null, true)
            );
        }

        return $this->directory->getResourceUrl($resource);
    }

    /**
     * Request a resource (URL is found using ACME server directory).
     *
     * @param string $method
     * @param string $resource
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     * @throws AcmeCoreClientException when an error occured during response parsing
     *
     * @return array|string
     */
    protected function requestResource($method, $resource, array $payload, $returnJson = true)
    {
        return $this->getHttpClient()->signedRequest(
            $method,
            $this->getResourceUrl($resource),
            $payload,
            $returnJson
        );
    }

    /**
     * Retrieve the resource account.
     *
     * @return string
     */
    private function getResourceAccount()
    {
        if (!$this->account) {
            $payload = [
                'onlyReturnExisting' => true,
            ];

            $this->requestResource('POST', ResourcesDirectory::NEW_ACCOUNT, $payload);
            $this->account = $this->getHttpClient()->getLastLocation();
        }

        return $this->account;
    }

    private function createAuthorizationChallenge($domain, array $response)
    {
        $base64encoder = $this->getHttpClient()->getBase64Encoder();

        return new AuthorizationChallenge(
            $domain,
            $response['status'],
            $response['type'],
            $response['url'],
            $response['token'],
            $response['token'].'.'.$base64encoder->encode($this->getHttpClient()->getJWKThumbprint())
        );
    }
}
