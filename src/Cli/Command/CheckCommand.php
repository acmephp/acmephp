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
use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
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
                new InputOption('solver', 's', InputOption::VALUE_REQUIRED, 'The type of challenge solver to use (available: http, dns, route53)', 'http'),
                new InputOption('no-test', 't', InputOption::VALUE_NONE, 'Whether or not internal tests should be disabled'),
                new InputArgument('domains', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The list of domains to check the authorization for'),
            ])
            ->setDescription('Ask the ACME server to check an authorization token you expose to prove you are the owner of a list of domains')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command asks the ACME server to check an authorization token
you exposed to prove you own a given list of domains.

Once you are the proved owner of the domains, you can request SSL certificates for those domains.

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
        $domains = $input->getArgument('domains');

        $solverName = strtolower($input->getOption('solver'));

        $this->debug('Locating solver', ['name' => $solverName]);

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

        $this->debug('Solver found', ['name' => $solverName]);

        /** @var ValidatorInterface $validator */
        $validator = $this->getContainer()->get('challenge_validator');

        $this->notice(sprintf('Loading the order related to the domains %s ...', implode(', ', $domains)));
        $order = null;
        if ($this->getRepository()->hasCertificateOrder($domains)) {
            $order = $this->getRepository()->loadCertificateOrder($domains);
        }

        $this->notice(sprintf('Loading the authorization token for domains %s ...', implode(', ', $domains)));
        foreach ($domains as $domain) {
            if ($order) {
                $authorizationChallenge = null;
                $authorizationChallenges = $order->getAuthorizationChallenges($domain);
                foreach ($authorizationChallenges as $challenge) {
                    if ($solver->supports($challenge)) {
                        $authorizationChallenge = $challenge;
                        break;
                    }
                }
                if (null === $authorizationChallenge) {
                    throw new ChallengeNotSupportedException();
                }
            } else {
                $authorizationChallenge = $repository->loadDomainAuthorizationChallenge($domain);
                if (!$solver->supports($authorizationChallenge)) {
                    throw new ChallengeNotSupportedException();
                }
            }
            $this->debug('Challenge loaded', ['challenge' => $authorizationChallenge->toArray()]);

            if (!$input->getOption('no-test')) {
                $this->notice('Testing the challenge...');
                if (!$validator->isValid($authorizationChallenge)) {
                    $this->output->writeln(sprintf('<info>Can not valid challenge for domain %s ...</info>', $domain));
                }
            }

            $this->notice(sprintf('Requesting authorization check for domain %s ...', $domain));
            $client->challengeAuthorization($authorizationChallenge);
        }

        $this->info(sprintf(<<<'EOF'

<info>The authorization check was successful!</info>

You are now the proved owner of those domains %s.
<info>Please note that you won't need to prove it anymore as long as you keep the same account key pair.</info>

You can now request a certificate for your domains:

   php <info>%s request</info> %s

EOF
            ,
            implode(', ', $domains),
            $_SERVER['PHP_SELF'],
            implode(' -a ', $domains)
        ));

        $solver->cleanup($authorizationChallenge);
    }
}
