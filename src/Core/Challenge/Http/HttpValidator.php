<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Validator for HTTP challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class HttpValidator implements ValidatorInterface
{
    /**
     * @var HttpDataExtractor
     */
    private $extractor;

    /**
     * @var Client
     */
    private $client;

    public function __construct(HttpDataExtractor $extractor = null, Client $client = null)
    {
        $this->extractor = null === $extractor ? new HttpDataExtractor() : $extractor;
        $this->client = null === $client ? new Client() : $client;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver)
    {
        return 'http-01' === $authorizationChallenge->getType() && !$solver instanceof MockServerHttpSolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver)
    {
        $checkUrl = $this->extractor->getCheckUrl($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        try {
            return $checkContent === trim($this->client->get($checkUrl, ['verify' => false])->getBody()->getContents());
        } catch (ClientException $e) {
            return false;
        }
    }
}
