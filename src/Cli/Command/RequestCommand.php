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

use AcmePhp\Cli\ActionHandler\ActionHandler;
use AcmePhp\Cli\Command\Helper\DistinguishedNameHelper;
use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Core\AcmeClientInterface;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\ParsedCertificate;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class RequestCommand extends AbstractCommand
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var AcmeClientInterface
     */
    private $client;

    /**
     * @var ActionHandler
     */
    private $actionHandler;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('request')
            ->setDefinition([
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain to get a certificate for'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Whether to force renewal or not (by default, renewal will be done only if the certificate expire in less than a week)'),
                new InputOption('country', null, InputOption::VALUE_REQUIRED, 'Your country two-letters code (field "C" of the distinguished name, for instance: "US")'),
                new InputOption('province', null, InputOption::VALUE_REQUIRED, 'Your country province (field "ST" of the distinguished name, for instance: "California")'),
                new InputOption('locality', null, InputOption::VALUE_REQUIRED, 'Your locality (field "L" of the distinguished name, for instance: "Mountain View")'),
                new InputOption('organization', null, InputOption::VALUE_REQUIRED, 'Your organization/company (field "O" of the distinguished name, for instance: "Acme PHP")'),
                new InputOption('unit', null, InputOption::VALUE_REQUIRED, 'Your unit/department in your organization (field "OU" of the distinguished name, for instance: "Sales")'),
                new InputOption('email', null, InputOption::VALUE_REQUIRED, 'Your e-mail address (field "E" of the distinguished name)'),
                new InputOption('alternate-domains', null, InputOption::VALUE_REQUIRED, 'Alternate domains for this certificate, separated by commas (use "none" to disable the question in the command)'),
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
        $this->repository = $this->getRepository();
        $this->client = $this->getClient();
        $this->actionHandler = $this->getActionHandler();

        $domain = $input->getArgument('domain');

        // Certificate renewal
        if ($this->repository->hasDomainKeyPair($domain)
            && $this->repository->hasDomainDistinguishedName($domain)
            && $this->repository->hasDomainCertificate($domain)) {
            return $this->executeRenewal($domain);
        }

        // Certificate first request
        return $this->executeFirstRequest($domain);
    }

    /**
     * Request a first certificate for the given domain.
     *
     * @param string $domain
     */
    private function executeFirstRequest($domain)
    {
        $introduction = <<<'EOF'

There is currently no certificate for domain %s in the Acme PHP storage. As it is the
first time you request a certificate for this domain, some configuration is required.
 
<info>Generating domain key pair...</info>
EOF;

        $this->output->writeln(sprintf($introduction, $domain));

        // Generate domain key pair
        $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair();
        $this->repository->storeDomainKeyPair($domain, $domainKeyPair);

        // Ask DistinguishedName
        $alternateDomains = $this->input->getOption('alternate-domains');

        if (empty($alternateDomains)) {
            $alternateDomains = null;
        } elseif ($alternateDomains === 'none') {
            $alternateDomains = [];
        } else {
            $alternateDomains = explode(',', $alternateDomains);
        }

        $distinguishedName = new DistinguishedName(
            $domain,
            $this->input->getOption('country'),
            $this->input->getOption('province'),
            $this->input->getOption('locality'),
            $this->input->getOption('organization'),
            $this->input->getOption('unit'),
            $this->input->getOption('email'),
            $alternateDomains ?: []
        );

        /** @var DistinguishedNameHelper $helper */
        $helper = $this->getHelper('distinguished_name');

        if (!$helper->isReadyForRequest($distinguishedName, $alternateDomains !== null)) {
            $this->output->writeln("\nSome informations are required for the certificate:\n");

            $distinguishedName = $helper->ask(
                $this->getHelper('question'),
                $this->input,
                $this->output,
                $distinguishedName,
                $alternateDomains !== null
            );
        }

        $this->repository->storeDomainDistinguishedName($domain, $distinguishedName);

        $this->output->writeln(
            '<info>Distinguished name informations have been stored locally for this domain (they won\'t be asked on renewal).</info>'
        );

        // Request
        $this->output->writeln(sprintf('<info>Requesting first certificate for domain %s ...</info>', $domain));
        $csr = new CertificateRequest($distinguishedName, $domainKeyPair);
        $response = $this->client->requestCertificate($domain, $csr);

        $this->repository->storeDomainCertificate($domain, $response->getCertificate());

        // Post-generate actions
        $this->output->writeln('<info>Running post-generate actions...</info>');
        $this->actionHandler->handle($response);

        // Success message
        /** @var ParsedCertificate $parsedCertificate */
        $parsedCertificate = $this->getContainer()->get('ssl.certificate_parser')->parse($response->getCertificate());

        $success = <<<'EOF'

<info>The SSL certificate was fetched successfully!</info>

This certificate is valid from now to %expiration%.

5 files were created in the Acme PHP storage directory:

    * <info>%private%</info> contains your domain private key (required in many cases). 

    * <info>%cert%</info> contains only your certificate, without the issuer certificate.
      It may be useful in certains cases but you will probably not need it (use fullchain.pem instead).

    * <info>%chain%</info> contains the issuer certificate chain (its certificate, the
      certificate of its issuer, the certificate of the issuer of its issuer, etc.). Your certificate is
      not present in this file.

    * <info>%fullchain%</info> contains your certificate AND the issuer certificate chain.
      You most likely will use this file in your webserver.

    * <info>%combined%</info> contains the fullchain AND your domain private key (some
      webservers expect this format such as haproxy).
      
Read the documentation at https://acmephp.github.io/documentation/ to learn more about how to
configure your web server and set up automatic renewal.

To renew your certificate manually, simply re-run this command.

EOF;

        $masterPath = $this->getContainer()->getParameter('app.storage_directory');

        $replacements = [
            '%expiration' => $parsedCertificate->getValidTo()->format(\DateTime::ISO8601),
            '%private%'   => $masterPath.'/private/'.$domain.'/private.pem',
            '%cert%'      => $masterPath.'/certs/'.$domain.'/cert.pem',
            '%chain%'     => $masterPath.'/certs/'.$domain.'/chain.pem',
            '%fullchain%' => $masterPath.'/certs/'.$domain.'/fullchain.pem',
            '%combined%'  => $masterPath.'/certs/'.$domain.'/combined.pem',
        ];

        $this->output->writeln(str_replace(array_keys($replacements), array_values($replacements), $success));
    }

    /**
     * Renew a given domain certificate.
     *
     * @param string $domain
     */
    private function executeRenewal($domain)
    {
        /** @var LoggerInterface $monitoringLogger */
        $monitoringLogger = $this->getContainer()->get('monitoring_factory')->createLogger();

        try {
            // Check expiration date to avoid too much renewal
            $certificate = $this->repository->loadDomainCertificate($domain);

            if (!$this->input->getOption('force')) {
                /** @var ParsedCertificate $parsedCertificate */
                $parsedCertificate = $this->getContainer()->get('ssl.certificate_parser')->parse($certificate);

                if ($parsedCertificate->getValidTo()->format('U') - time() >= 604800) {
                    $monitoringLogger->debug('Certificate does not need renewal', [
                        'domain'      => $domain,
                        'valid_until' => $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                    ]);

                    $this->output->writeln(sprintf(
                        '<info>Current certificate is valid until %s, renewal is not necessary. Use --force to force renewal.</info>',
                        $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'))
                    );

                    return;
                }

                $monitoringLogger->debug('Certificate needs renewal', [
                    'domain'      => $domain,
                    'valid_until' => $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                ]);

                $this->output->writeln(sprintf(
                    '<info>Current certificate will expire in less than a week (%s), renewal is required.</info>',
                    $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'))
                );
            } else {
                $this->output->writeln('<info>Forced renewal.</info>');
            }

            // Key pair
            $this->output->writeln('Loading domain key pair...');
            $domainKeyPair = $this->repository->loadDomainKeyPair($domain);

            // Distinguished name
            $this->output->writeln('Loading domain distinguished name...');
            $distinguishedName = $this->repository->loadDomainDistinguishedName($domain);

            // Renewal
            $this->output->writeln(sprintf('Renewing certificate for domain %s ...', $domain));
            $csr = new CertificateRequest($distinguishedName, $domainKeyPair);
            $response = $this->client->requestCertificate($domain, $csr);
            $this->repository->storeDomainCertificate($domain, $response->getCertificate());

            // Post-generate actions
            $this->output->writeln('Running post-generate actions...');
            $this->actionHandler->handle($response);

            $this->output->writeln('<info>Certificate renewed successfully!</info>');

            $monitoringLogger->info('Certificate renewed successfully', ['domain' => $domain]);
        } catch (\Exception $e) {
            $monitoringLogger->alert('A critical error occured during certificate renewal', ['exception' => $e]);
        } catch (\Throwable $e) {
            $monitoringLogger->alert('A critical error occured during certificate renewal', ['exception' => $e]);
        }
    }
}
