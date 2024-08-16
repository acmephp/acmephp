<?php

declare(strict_types=1);

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge;

use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * A strategy ACME validator.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ChainValidator implements ValidatorInterface
{
    /** @var ValidatorInterface[] */
    private $validators;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
        $this->validators = $validators;
    }

    public function supports(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports($authorizationChallenge, $solver)) {
                return true;
            }
        }

        return false;
    }

    public function isValid(AuthorizationChallenge $authorizationChallenge, SolverInterface $solver): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports($authorizationChallenge, $solver)) {
                return $validator->isValid($authorizationChallenge, $solver);
            }
        }

        throw new ChallengeNotSupportedException();
    }
}
