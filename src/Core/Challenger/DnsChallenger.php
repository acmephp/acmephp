<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenger;

use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACME DNS challenger with manual configuration.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DnsChallenger implements ChallengerInterface
{
    /**
     * @var Base64SafeEncoder
     */
    private $encoder;

    /**
     * @var OutputInterface
     */
    protected $output;


    /**
     * @param OutputInterface $output
     */
    public function __construct(Base64SafeEncoder $encoder = null, OutputInterface $output = null)
    {
        $this->output = null === $output ? new ConsoleOutput() : $output;
        $this->encoder = null === $encoder ? new Base64SafeEncoder() : $encoder;
    }

    /**
     * @inheritdoc
     */
    public function supports($type)
    {
        return 'dns-01' === $type;
    }

    protected function getEntryName(AuthorizationChallenge $authorizationChallenge)
    {
        return sprintf('_acme-challenge.%s.', $authorizationChallenge->getDomain());
    }

    protected function getEntryValue(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->encoder->encode(hash('sha256', $authorizationChallenge->getPayload(), true));
    }

    /**
     * @inheritdoc
     */
    public function initialize(AuthorizationChallenge $authorizationChallenge)
    {
        $entryName = $this->getEntryName($authorizationChallenge);
        $entryValue = $this->getEntryValue($authorizationChallenge);

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
                $entryName,
                $entryValue,
                $entryName,
                $entryName,
                $entryValue
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $entryName = $this->getEntryName($authorizationChallenge);

        $this->output->writeln(
            sprintf(
                <<<'EOF'
<info>The authorization token was successfully checked!</info>

You can now cleanup your DNS by removing the domain <comment>_acme-challenge.%s.</comment>
EOF
                ,
                $entryName
            )
        );
    }
}
