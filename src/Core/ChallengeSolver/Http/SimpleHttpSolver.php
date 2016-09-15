<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\ChallengeSolver\Http;

use AcmePhp\Core\ChallengeSolver\SolverInterface;
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
     * @var HttpValidator
     */
    private $validator;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param HttpDataExtractor $extractor
     * @param HttpValidator     $validator
     * @param OutputInterface   $output
     */
    public function __construct(
        HttpDataExtractor $extractor = null,
        HttpValidator $validator = null,
        OutputInterface $output = null
    ) {
        $this->extractor = null === $extractor ? new HttpDataExtractor() : $extractor;
        $this->validator = null === $validator ? new HttpValidator() : $validator;
        $this->output = null === $output ? new NullOutput() : $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        return 'http-01' === $type;
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

EOF
                ,
                $checkUrl,
                $checkContent
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AuthorizationChallenge $authorizationChallenge, $timeout = 60)
    {
        $checkUrl = $this->extractor->getCheckUrl($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        $this->validator->validate($checkUrl, $checkContent, $timeout);
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
                    You can now safety remove the challenge's file at %s

EOF
                ,
                $checkUrl
            )
        );
    }
}
