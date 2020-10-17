<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Component\Console\Output\NullOutput;
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
     * @param DnsDataExtractor $extractor
     * @param OutputInterface  $output
     */
    public function __construct(DnsDataExtractor $extractor = null, OutputInterface $output = null)
    {
        $this->extractor = $extractor ?: new DnsDataExtractor();
        $this->output = $output ?: new NullOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'dns-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);
        $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
    Add the following TXT record to your DNS zone
        Domain: %s
        TXT value: %s
        
    <comment>Wait for the propagation before moving to the next step</comment>
    Tips: Use the following command to check the propagation

        host -t TXT %s

EOF
                ,
                $recordName,
                $recordValue,
                $recordName
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
You can now cleanup your DNS by removing the domain <comment>_acme-challenge.%s.</comment>
EOF
                ,
                $recordName
            )
        );
    }
}
