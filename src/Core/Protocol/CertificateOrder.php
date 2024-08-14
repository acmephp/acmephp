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
    public function __construct(
        private array $authorizationsChallenges,
        private readonly ?string $orderEndpoint = null,
        private readonly ?string $status = null)
    {
        foreach ($authorizationsChallenges as &$authorizationChallenges) {
            foreach ($authorizationChallenges as &$authorizationChallenge) {
                if (\is_array($authorizationChallenge)) {
                    $authorizationChallenge = AuthorizationChallenge::fromArray($authorizationChallenge);
                }
            }
        }
    }

    public function toArray(): array
    {
        $authorizationsChallenges = array_map(
            fn ($challenges): array => array_map(
                fn ($challenge): array => $challenge->toArray(),
                $challenges
            ),
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
    public function getAuthorizationsChallenges(): array
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
