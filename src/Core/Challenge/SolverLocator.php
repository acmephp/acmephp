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

use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ACME Challenge solver locator provides methods to find registered solvers.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SolverLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string[]
     */
    private $solvers;

    /**
     * @param ContainerInterface $container
     * @param \string[]          $solvers
     */
    public function __construct(ContainerInterface $container, array $solvers)
    {
        $this->container = $container;
        $this->solvers = $solvers;
    }

    /**
     * Returns the solvers registered with the given name.
     *
     * @param string $solverName
     *
     * @return SolverInterface
     */
    public function getSolver($solverName)
    {
        if (!$this->hasSolver($solverName)) {
            throw new ChallengeNotSupportedException();
        }

        return $this->container->get($this->solvers[$solverName]);
    }

    /**
     * Returns whether or not the given solver had been registered.
     *
     * @param string $solverName
     *
     * @return bool
     */
    public function hasSolver($solverName)
    {
        return array_key_exists($solverName, $this->solvers);
    }

    /**
     * Returns the list of registered solver's names.
     *
     * @return string[]
     */
    public function getSolversName()
    {
        return array_keys($this->solvers);
    }
}
