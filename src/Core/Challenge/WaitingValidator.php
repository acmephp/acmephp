<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge;

use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * ACME Challenge validator who implement a retry strategy till the decorated validator successfully validate the
 * challenge or the timeout is reached.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class WaitingValidator implements ValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly int $timeout = 180,
    ) {
    }

    public function supports(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver): bool
    {
        return $this->validator->supports($authorizationChallenge, $solver);
    }

    public function isValid(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver): bool
    {
        $limitEndTime = time() + $this->timeout;

        do {
            if ($this->validator->isValid($authorizationChallenge, $solver)) {
                return true;
            }
            sleep(3);
        } while ($limitEndTime > time());

        return false;
    }
}
