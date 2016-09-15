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
     * @var DnsValidator
     */
    private $validator;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param DnsDataExtractor $extractor
     * @param DnsValidator     $validator
     * @param OutputInterface  $output
     */
    public function __construct(
        DnsDataExtractor $extractor = null,
        DnsValidator $validator = null,
        OutputInterface $output = null
    ) {
        $this->extractor = null === $extractor ? new DnsDataExtractor() : $extractor;
        $this->validator = null === $validator ? new DnsValidator() : $validator;
        $this->output = null === $output ? new NullOutput() : $output;
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

EOF
                ,
                $recordName,
                $recordValue
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AuthorizationChallenge $authorizationChallenge, $timeout = 60)
    {
        $recordName = $this->extractor->getRecordName($authorizationChallenge);
        $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

        $this->validator->validate($recordName, $recordValue, $timeout);
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
