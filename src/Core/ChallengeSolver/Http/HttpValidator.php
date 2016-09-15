<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\ChallengeSolver\Http;

use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Validator for HTTP challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class HttpValidator
{
    /**
     * @var ClientInterface|null
     */
    private $client;

    /**
     * @param ClientInterface|null $client
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = null === $client ? new Client() : $client;
    }

    /**
     * Internally validate the challenge by performing the same kind of test than the CA.
     *
     * @param string $checkUrl
     * @param string $checkContent
     * @param int    $timeout
     */
    public function validate($checkUrl, $checkContent, $timeout = 60)
    {
        $limitEndTime = microtime(true) + $timeout;

        do {
            if ($this->isValide($checkUrl, $checkContent)) {
                return;
            }

            sleep(1);
        } while ($limitEndTime > microtime(true));

        throw new ChallengeTimedOutException('Unable to validate timeout in the given time');
    }

    /**
     * Returns whether or not the url return the expected content.
     *
     * @param string $checkUrl
     * @param string $checkContent
     *
     * @return bool
     */
    public function isValide($checkUrl, $checkContent)
    {
        try {
            $content = $this->client->get($checkUrl)->getBody()->getContents();

            return $checkContent === $content;
        } catch (ClientException $e) {
            return false;
        }
    }
}
