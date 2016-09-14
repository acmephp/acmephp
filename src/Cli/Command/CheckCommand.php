<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command;

use AcmePhp\Core\Challenger\ChallengerInterface;
use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class CheckCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('check')
            ->setDefinition([
                new InputOption('challenge', 'c', InputOption::VALUE_REQUIRED, 'The challenge to use (http, dns)', 'http'),
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain to check the authorization for'),
            ])
            ->setDescription('Ask the ACME server to check an authorization token you expose to prove you are the owner of a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command asks the ACME server to check an authorization token
you exposed to prove you own a given domain.

Once you are the proved owner of a domain, you can request SSL certificates for this domain.

Use the <info>authorize</info> command before this one.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getRepository();
        $client = $this->getClient();
        $domain = $input->getArgument('domain');

        $challengerName = strtolower($input->getOption('challenge'));
        if (!$this->getContainer()->has('challenger.'.$challengerName)) {
            throw new \UnexpectedValueException(sprintf('The challenge "%s" does not exists', $challengerName));
        }
        /** @var ChallengerInterface $challenger */
        $challenger = $this->getContainer()->get('challenger.'.$challengerName);

        $output->writeln(sprintf('<info>Loading the authorization token for domain %s ...</info>', $domain));
        $authorization = $repository->loadDomainAuthorizationChallenge($domain);

        $output->writeln(sprintf('<info>Requesting authorization check for domain %s ...</info>', $domain));
        $client->challengeAuthorization($challenger, $authorization);

        $this->output->writeln(sprintf(<<<'EOF'

<info>The authorization check was successful!</info>

You are now the proved owner of the domain %s.
<info>Please note that you won't need to prove it anymore as long as you keep the same account key pair.</info>

You can now request a certificate for your domain:

   php <info>%s request</info> %s

EOF
            ,
            $domain,
            $_SERVER['PHP_SELF'],
            $domain
        ));
    }
}
