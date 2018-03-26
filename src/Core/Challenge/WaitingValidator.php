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
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @param ValidatorInterface $validator
     * @param int                $timeout
     */
    public function __construct(ValidatorInterface $validator, $timeout = 180)
    {
        $this->validator = $validator;
        $this->timeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->validator->supports($authorizationChallenge);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(AuthorizationChallenge $authorizationChallenge)
    {
        $limitEndTime = time() + $this->timeout;

        do {
            if ($this->validator->isValid($authorizationChallenge)) {
                return true;
            }
            sleep(3);
        } while ($limitEndTime > time());

        return false;
    }
}
