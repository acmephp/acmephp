<?php

/*
 * This file is part of the ACME PHP library.
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
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
        $this->validators = $validators;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge)
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports($authorizationChallenge)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(AuthorizationChallenge $authorizationChallenge)
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports($authorizationChallenge)) {
                return $validator->isValid($authorizationChallenge);
            }
        }

        throw new ChallengeNotSupportedException();
    }
}
