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

use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * ACME challenge solver.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface SolverInterface
{
    /**
     * Determines whether or not the solver supports a given Challenge.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     *
     * @return bool The solver supports the given challenge's type
     */
    public function supports(AuthorizationChallenge $authorizationChallenge);

    /**
     * Solve the given authorization challenge.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     */
    public function solve(AuthorizationChallenge $authorizationChallenge);

    /**
     * Cleanup the environments after a successful challenge.
     *
     * @param AuthorizationChallenge $authorizationChallenge
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge);
}
