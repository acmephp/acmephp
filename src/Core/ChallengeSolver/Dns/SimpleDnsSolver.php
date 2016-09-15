<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\ChallengeSolver\Dns;

use AcmePhp\Core\ChallengeSolver\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACME DNS solver with manual intervention.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SimpleDnsSolver implements SolverInterface
{
    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(DnsDataExtractor $extractor = null, OutputInterface $output = null)
    {
        $this->output = null === $output ? new ConsoleOutput() : $output;
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        return 'dns-01' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(AuthorizationChallenge $authorizationChallenge)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);
        $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
<info>The authorization token was successfully fetched!</info>

Now, to prove you own the domain %s and request certificates for this domain, follow these steps:

    1. Add the following TXT record do you DNS zone
         Domain: %s
         TXT value: %s

    2. Check in your terminal that the following command returns the following response
       
         $ host -t TXT %s
         %s descriptive text "%s"
EOF
                ,
                $authorizationChallenge->getDomain(),
                $recordName,
                $recordValue,
                $recordName,
                $recordName,
                $recordValue
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
<info>The authorization token was successfully checked!</info>

You can now cleanup your DNS by removing the domain <comment>_acme-challenge.%s.</comment>
EOF
                ,
                $recordName
            )
        );
    }
}
