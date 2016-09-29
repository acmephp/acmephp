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

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Challenge\SolverLocator;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AuthorizeCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('authorize')
            ->setDefinition([
                new InputOption('solver', 's', InputOption::VALUE_REQUIRED, 'The type of challenge solver to use', 'http'),
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain to ask an authorization for'),
            ])
            ->setDescription('Ask the ACME server for an authorization token to check you are the owner of a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command asks the ACME server for an authorization token.
You will then have to expose that token on a specific URL under that domain and ask for
the server to check you are the own of the domain by checking this URL.

Ask the server for an authorization token:

  <info>php %command.full_name% example.com</info>
  
Follow the instructions to expose your token on the specific URL, and then run the <info>check</info>
command to tell the server to check your token.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getClient();
        $domain = $input->getArgument('domain');

        $solverName = strtolower($input->getOption('solver'));
        /** @var SolverLocator $solverLocator */
        $solverLocator = $this->getContainer()->get('challenge_solver.locator');
        if (!$solverLocator->hasSolver($solverName)) {
            throw new \UnexpectedValueException(sprintf(
                'The solver "%s" does not exists. Available solvers are: (%s)',
                $solverName,
                implode(', ', $solverLocator->getSolversName())
            ));
        }
        /** @var SolverInterface $solver */
        $solver = $solverLocator->getSolver($solverName);

        $output->writeln(sprintf('<info>Requesting an authorization token for domain %s ...</info>', $domain));

        $authorizationChallenges = $client->requestAuthorization($domain);
        $authorizationChallenge = null;
        foreach ($authorizationChallenges as $candidate) {
            if ($solver->supports($candidate)) {
                $authorizationChallenge = $candidate;
                break;
            }
        }

        if (null === $authorizationChallenge) {
            throw new ChallengeNotSupportedException();
        }

        $this->getRepository()->storeDomainAuthorizationChallenge($domain, $authorizationChallenge);

        $this->output->writeln('<info>The authorization token was successfully fetched!</info>');
        $solver->solve($authorizationChallenge);

        $this->output->writeln(sprintf(
<<<'EOF'
<info>Then, you can ask to the CA to check the challenge!</info>
    Call the <info>check</info> command to ask the server to check your URL:

    php <info>%s check</info> -s %s %s

EOF
            ,
            $_SERVER['PHP_SELF'],
            $solverName,
            $domain
        ));
    }
}
