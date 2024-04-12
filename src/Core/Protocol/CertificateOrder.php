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

/**
 * Represent an ACME order.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CertificateOrder
{
    /** @var AuthorizationChallenge[][] */
    private $authorizationsChallenges;

    /** @var string */
    private $orderEndpoint;

    /** @var string */
    private $status;

    public function __construct(array $authorizationsChallenges, string $orderEndpoint = null, string $status = null)
    {
        foreach ($authorizationsChallenges as &$authorizationChallenges) {
            foreach ($authorizationChallenges as &$authorizationChallenge) {
                if (\is_array($authorizationChallenge)) {
                    $authorizationChallenge = AuthorizationChallenge::fromArray($authorizationChallenge);
                }
            }
        }

        $this->authorizationsChallenges = $authorizationsChallenges;
        $this->orderEndpoint = $orderEndpoint;
        $this->status = $status;
    }

    public function toArray(): array
    {
        $authorizationsChallenges = array_map(
            function ($challenges): array {
                return array_map(
                    function ($challenge): array {
                        return $challenge->toArray();
                    },
                    $challenges
                );
            },
            $this->getAuthorizationsChallenges()
        );

        return [
            'authorizationsChallenges' => $authorizationsChallenges,
            'orderEndpoint' => $this->getOrderEndpoint(),
            'status' => $this->getStatus(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['authorizationsChallenges'], $data['orderEndpoint']);
    }

    /**
     * @return AuthorizationChallenge[][]
     */
    public function getAuthorizationsChallenges()
    {
        return $this->authorizationsChallenges;
    }

    /**
     * @return AuthorizationChallenge[]
     */
    public function getAuthorizationChallenges(string $domain): array
    {
        $domain = strtolower($domain);

        if (!isset($this->authorizationsChallenges[$domain])) {
            throw new AcmeCoreClientException('The order does not contains any authorization challenge for the domain '.$domain);
        }

        return $this->authorizationsChallenges[$domain];
    }

    public function getOrderEndpoint(): string
    {
        return $this->orderEndpoint;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
