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

use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class RequestCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('request')
            ->setDefinition([
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain to get a certificate for'),
            ])
            ->setDescription('Request a SSL certificate for a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command requests to the ACME server a SSL certificate for a
given domain.

This certificate will be stored in the Acme PHP storage directory.

You need to be the proved owner of the domain you ask a certificate for. To prove your ownership
of the domain, please use commands <info>authorize</info> and <info>check</info> before this one.
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

        $output->write("\n");

        /*
         * Generate domain key pair if needed
         */
        if (!$repository->hasDomainKeyPair($domain)) {
            $output->writeln('<info>No domain key pair was found, generating one...</info>');

            /** @var KeyPair $domainKeyPair */
            $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair();

            $repository->storeDomainKeyPair($domain, $domainKeyPair);
        }

        $this->output->writeln('<info>Loading domain key pair...</info>');
        $domainKeyPair = $repository->loadDomainKeyPair($domain);

        $output->write("\n");

        /*
         * Generate domain distinguished name if needed
         */
        if (!$repository->hasDomainDistinguishedName($domain)) {
            $output->writeln("<info>No domain distinguished name was found, creating one...</info>\n");

            $helper = $this->getHelper('question');

            $countryName = $helper->ask($input, $output, new Question(
                'What is the two-letters code of your country (field "C" of the distinguished name)? : ',
                'FR'
            ));

            $stateOrProvinceName = $helper->ask($input, $output, new Question(
                'What is the full name of your country province (field "ST" of the distinguished name)? : '
            ));

            $localityName = $helper->ask($input, $output, new Question(
                'What is the full name of your locality (field "L" of the distinguished name)? : '
            ));

            $organizationName = $helper->ask($input, $output, new Question(
                'What is the full name of your organization/company (field "O" of the distinguished name)? : '
            ));

            $organizationalUnitName = $helper->ask($input, $output, new Question(
                'What is the full name of your organization/company unit or department (field "OU" of the distinguished name)? : '
            ));

            $emailAddress = $helper->ask($input, $output, new Question(
                'What is your e-mail address (field "E" of the distinguished name)? : '
            ));

            $repository->storeDomainDistinguishedName($domain, new DistinguishedName(
                $domain,
                $countryName,
                $stateOrProvinceName,
                $localityName,
                $organizationName,
                $organizationalUnitName,
                $emailAddress
            ));

            $output->writeln('<info>The distinguished name of this domain has been stored locally, it won\'t be asked on renewal.</info>');
        }

        $distinguishedName = $repository->loadDomainDistinguishedName($domain);

        /*
         * Request certificate
         */
        $output->writeln('<info>Creating Certificate Signing Request ...</info>');
        $csr = new CertificateRequest($distinguishedName, $domainKeyPair);

        $output->writeln(sprintf('<info>Requesting certificate for domain %s ...</info>', $domain));
        $response = $client->requestCertificate($domain, $csr);

        $repository->storeDomainCertificate($domain, $response->getCertificate());

        $this->output->writeln(sprintf(<<<'EOF'

<info>The SSL certificate was fetched successfully!</info>

It has been stored in ~/.acmephp/master/certs. You can use it in your webserver.

EOF
        ));
    }
}
