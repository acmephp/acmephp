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

use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * ACME challenge solver able to solve several challenges at once.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface MultipleChallengesSolverInterface extends SolverInterface
{
    /**
     * Solve the given list of authorization challenge.
     *
     * @param AuthorizationChallenge[] $authorizationChallenges
     */
    public function solveAll(array $authorizationChallenges);

    /**
     * Cleanup the environments after all challenges.
     *
     * @param AuthorizationChallenge[] $authorizationChallenges
     */
    public function cleanupAll(array $authorizationChallenges);
}
