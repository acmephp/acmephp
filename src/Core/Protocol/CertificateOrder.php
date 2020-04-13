<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use AcmePhp\Core\Exception\AcmeCoreClientException;
use Webmozart\Assert\Assert;

/**
 * Represent an ACME order.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CertificateOrder
{
    /**
     * @var AuthorizationChallenge[][]
     */
    private $authorizationsChallenges;

    /**
     * @var string
     */
    private $orderEndpoint;

    /**
     * @param string $domain
     * @param string $type
     * @param string $url
     * @param string $token
     * @param string $payload
     * @param string $order
     */
    public function __construct($authorizationsChallenges, $orderEndpoint = null)
    {
        Assert::isArray($authorizationsChallenges, 'Challenge::$authorizationsChallenges expected an array. Got: %s');
        Assert::nullOrString($orderEndpoint, 'Challenge::$orderEndpoint expected a string or null. Got: %s');

        foreach ($authorizationsChallenges as &$authorizationChallenges) {
            foreach ($authorizationChallenges as &$authorizationChallenge) {
                if (\is_array($authorizationChallenge)) {
                    $authorizationChallenge = AuthorizationChallenge::fromArray($authorizationChallenge);
                }
            }
        }

        $this->authorizationsChallenges = $authorizationsChallenges;
        $this->orderEndpoint = $orderEndpoint;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'authorizationsChallenges' => $this->getAuthorizationsChallenges(),
            'orderEndpoint' => $this->getOrderEndpoint(),
        ];
    }

    /**
     * @return AuthorizationChallenge
     */
    public static function fromArray(array $data)
    {
        return new self(
            $data['authorizationsChallenges'],
            $data['orderEndpoint']
        );
    }

    /**
     * @return AuthorizationChallenge[][]
     */
    public function getAuthorizationsChallenges()
    {
        return $this->authorizationsChallenges;
    }

    /**
     * @param string $domain
     *
     * @return AuthorizationChallenge[]
     */
    public function getAuthorizationChallenges($domain)
    {
        if (!isset($this->authorizationsChallenges[$domain])) {
            throw new AcmeCoreClientException('The order does not contains any authorization challenge for the domain '.$domain);
        }

        return $this->authorizationsChallenges[$domain];
    }

    /**
     * @return string
     */
    public function getOrderEndpoint()
    {
        return $this->orderEndpoint;
    }
}
