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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

        $output->writeln(sprintf('<info>Requesting an authorization token for domain %s ...</info>', $domain));
        $authorization = $client->requestAuthorization($domain);

        $this->getRepository()->storeDomainAuthorizationChallenge($domain, $authorization);

        $this->output->writeln(sprintf(<<<'EOF'

<info>The authorization token was successfully fetched!</info>

Now, to prove you own the domain %s and request certificates for this domain, follow these steps:

    1. Create a text file accessible on URL http://%s/.well-known/acme-challenge/%s
       containing the following content:
       
       %s
       
    2. Check in your browser that the URL http://%s/.well-known/acme-challenge/%s returns
       the authorization token above.
       
    3. Call the <info>check</info> command to ask the server to check your URL:
       
       php <info>%s check</info> %s

EOF
            ,
            $domain,
            $domain,
            $authorization->getToken(),
            $authorization->getPayload(),
            $domain,
            $authorization->getToken(),
            $_SERVER['PHP_SELF'],
            $domain
        ));
    }
}
