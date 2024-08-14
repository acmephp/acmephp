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

use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\ClientInterface;

/**
 * Guzzle HTTP client wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SecureHttpClientFactory
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly Base64SafeEncoder $base64Encoder,
        private readonly KeyParser $keyParser,
        private readonly DataSigner $dataSigner,
        private readonly ServerErrorHandler $errorHandler,
    ) {
    }

    /**
     * Create a SecureHttpClient using a given account KeyPair.
     */
    public function createSecureHttpClient(KeyPair $accountKeyPair): SecureHttpClient
    {
        return new SecureHttpClient(
            $accountKeyPair,
            $this->httpClient,
            $this->base64Encoder,
            $this->keyParser,
            $this->dataSigner,
            $this->errorHandler
        );
    }
}
