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

use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
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
                new InputOption('solver', 's', InputOption::VALUE_REQUIRED, 'The type of challenge solver to use (available: http, dns, route53)', 'http'),
                new InputArgument('domains', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'List of domains to ask an authorization for'),
            ])
            ->setDescription('Ask the ACME server for an authorization token to check you are the owner of a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command asks the ACME server for an authorization token.
You will then have to expose that token on a specific URL under that domain and ask for
the server to check you are the own of the domain by checking this URL.

Ask the server for an authorization token:

  <info>php %command.full_name% example.com www.exemple.org *.example.io</info>
  
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
        $this->error('This command is deprecated. Use command "run" instead');

        $client = $this->getClient();
        $domains = $input->getArgument('domains');

        $solverName = strtolower($input->getOption('solver'));

        $this->debug('Locating solver', ['name' => $solverName]);

        $solverLocator = $this->getContainer()->get('acmephp.challenge_solver.locator');
        /** @var SolverInterface $solver */
        $solver = $solverLocator->get($solverName);
        $this->debug('Solver found', ['name' => $solverName]);

        $this->notice(sprintf('Requesting an authorization token for domains %s ...', implode(', ', $domains)));
        $order = $client->requestOrder($domains);
        $this->notice('The authorization tokens was successfully fetched!');
        $authorizationChallengesToSolve = [];
        foreach ($order->getAuthorizationsChallenges() as $domainKey => $authorizationChallenges) {
            $authorizationChallenge = null;
            foreach ($authorizationChallenges as $candidate) {
                if ($solver->supports($candidate)) {
                    $authorizationChallenge = $candidate;

                    $this->debug('Authorization challenge supported by solver', [
                        'solver' => $solverName,
                        'challenge' => $candidate->getType(),
                    ]);

                    break;
                }

                $this->debug('Authorization challenge not supported by solver', [
                    'solver' => $solverName,
                    'challenge' => $candidate->getType(),
                ]);
            }
            if (null === $authorizationChallenge) {
                throw new ChallengeNotSupportedException();
            }
            $this->debug('Storing authorization challenge', [
                'domain' => $domainKey,
                'challenge' => $authorizationChallenge->toArray(),
            ]);

            $this->getRepository()->storeDomainAuthorizationChallenge($domainKey, $authorizationChallenge);
            $authorizationChallengesToSolve[] = $authorizationChallenge;
        }
        if ($solver instanceof MultipleChallengesSolverInterface) {
            $solver->solveAll($authorizationChallengesToSolve);
        } else {
            /** @var AuthorizationChallenge $authorizationChallenge */
            foreach ($authorizationChallengesToSolve as $authorizationChallenge) {
                $this->info('Solving authorization challenge for domain', [
                    'domain' => $authorizationChallenge->getDomain(),
                    'challenge' => $authorizationChallenge->toArray(),
                ]);
                $solver->solve($authorizationChallenge);
            }
        }

        $this->getRepository()->storeCertificateOrder($domains, $order);

        $this->info(sprintf(
<<<'EOF'
<info>Then, you can ask to the CA to check the challenge!</info>
    Call the <info>check</info> command to ask the server to check your URL:

    php <info>%s check</info> -s %s %s

EOF
            ,
            $_SERVER['PHP_SELF'],
            $solverName,
            implode(' ', array_keys($order->getAuthorizationsChallenges()))
        ));
    }
}
