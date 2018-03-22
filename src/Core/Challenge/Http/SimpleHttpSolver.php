<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACME HTTP solver with manual intervention.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SimpleHttpSolver implements SolverInterface
{
    /**
     * @var HttpDataExtractor
     */
    private $extractor;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param HttpDataExtractor $extractor
     * @param OutputInterface   $output
     */
    public function __construct(HttpDataExtractor $extractor = null, OutputInterface $output = null)
    {
        $this->extractor = null === $extractor ? new HttpDataExtractor() : $extractor;
        $this->output = null === $output ? new NullOutput() : $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge)
    {
        return 'http-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        $checkUrl = $this->extractor->getCheckUrl($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
    Create a text file accessible on URL %s
    containing the following content:

    %s
    
    Check in your browser that the URL %s returns
    the authorization token above.

EOF
                ,
                $checkUrl,
                $checkContent,
                $checkContent
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $checkUrl = $this->extractor->getCheckUrl($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
                    You can now safely remove the challenge's file at %s

EOF
                ,
                $checkUrl
            )
        );
    }
}
