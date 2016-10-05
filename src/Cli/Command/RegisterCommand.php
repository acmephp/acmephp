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

use AcmePhp\Ssl\KeyPair;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class RegisterCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('register')
            ->setDefinition([
                new InputArgument('email', InputArgument::OPTIONAL, 'An e-mail to use when certificates will expire soon'),
                new InputOption('agreement', null, InputOption::VALUE_REQUIRED, 'The server usage conditions you agree with (automatically agreed with all licenses)'),
            ])
            ->setDescription('Register your account private key in the ACME server')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command register your account key in the ACME server
provided by the option --server (by default it will use Let's Encrypt servers).
This command will generate an account key if no account key exists in the storage.

You can add an e-mail that will be added to your registration (required for Let's Encrypt):

  <info>php %command.full_name% acmephp@example.com</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getRepository();

        /*
         * Generate account key pair if needed
         */
        if (!$repository->hasAccountKeyPair()) {
            $output->writeln('<info>No account key pair was found, generating one...</info>');

            /** @var KeyPair $accountKeyPair */
            $accountKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair();

            $repository->storeAccountKeyPair($accountKeyPair);
        }

        /*
         * Register on server
         */
        $client = $this->getClient();

        $email = $input->getArgument('email') ?: null;
        $agreement = $input->getOption('agreement') ?: null;

        $output->writeln('<info>Registering on the ACME server...</info>');
        $client->registerAccount($agreement, $email);

        $output->writeln('<info>Account registered successfully!</info>');
    }
}
