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
 * ACME challenge pre validator.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface ValidatorInterface
{
    /**
     * Determines whether or not the validator supports a given Challenge.
     *
     * @return bool The validator supports the given challenge's type
     */
    public function supports(AuthorizationChallenge $authorizationChallenge);

    /**
     * Internally validate the challenge by performing the same kind of test than the CA.
     *
     * @return bool The challenge is valid
     */
    public function isValid(AuthorizationChallenge $authorizationChallenge);
}
