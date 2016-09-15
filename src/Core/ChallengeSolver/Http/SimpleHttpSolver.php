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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACME HTTP solver with manual intervention.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SimpleHttpSolver implements SolverInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var HttpDataExtractor
     */
    private $extractor;

    /**
     * @param HttpDataExtractor $extractor
     * @param OutputInterface   $output
     */
    public function __construct(HttpDataExtractor $extractor = null, OutputInterface $output = null)
    {
        $this->output = null === $output ? new ConsoleOutput() : $output;
        $this->extractor = null === $extractor ? new HttpDataExtractor() : $extractor;
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
    public function initialize(AuthorizationChallenge $authorizationChallenge)
    {
        $checkUrl = $this->extractor->getCheckUrl($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
<info>The authorization token was successfully fetched!</info>

Now, to prove you own the domain %s and request certificates for this domain, follow these steps:

    1. Create a text file accessible on URL %s
       containing the following content:
       
       %s
       
    2. Check in your browser that the URL %s returns
       the authorization token above.
EOF
                ,
                $authorizationChallenge->getDomain(),
                $checkUrl,
                $checkContent,
                $checkUrl
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
    }
}
