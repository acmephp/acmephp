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
use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Core\AcmeClientInterface;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\ParsedCertificate;
use AcmePhp\Ssl\Parser\CertificateParser;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class RenewCommand extends AbstractCommand
{
    /** @var LoggerInterface */
    private $monitoringLogger;

    /** @var RepositoryInterface */
    private $repository;

    /** @var CertificateParser */
    private $certificateParser;

    /** @var AcmeClientInterface */
    private $client;

    /** @var ActionHandler */
    private $actionHandler;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('renew')
            ->setDefinition([
                new InputArgument('domain', InputArgument::OPTIONAL, 'An optionnal domain to renew the certificate for (if not provided, al certificates needing renewal will be renewed)'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Whether to force renewal or not (requires a domain)'),
            ])
            ->setDescription('Renew SSL certificates needing a renewal (ie. certificates expiring in less than a week)')
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
        $this->monitoringLogger = $this->getContainer()->get('monitoring_factory')->createLogger();
        $this->repository = $this->getRepository();
        $this->certificateParser = $this->getContainer()->get('ssl.certificate_parser');
        $this->client = $this->getClient();
        $this->actionHandler = $this->getActionHandler();

        $domain = $input->getArgument('domain');

        if (!$domain) {
            return $this->renewCertificatesAboutToExpire();
        }

        return $this->renewSingleCertificate($domain);
    }

    /**
     * Renew all the certificates about to expire (in less than a week).
     *
     * @return int
     */
    private function renewCertificatesAboutToExpire()
    {
        $directories = $this->getContainer()->get('repository.master_storage')->listContents('certs');

        foreach ($directories as $directory) {
            $domain = $directory['basename'];
            $parsedCertificate = $this->certificateParser->parse($this->repository->loadDomainCertificate($domain));
        }
    }

    /**
     * Renew a single domain certificate.
     *
     * @param string $domain
     *
     * @return int
     */
    private function renewSingleCertificate($domain)
    {
        if (!$this->repository->hasDomainCertificate($domain)) {
            $this->output->writeln('<error>This domain current certificate was not found.</error>');
            $this->output->writeln('Are you sure you requested a first time the certificate before trying to renew it?');
            $this->output->writeln('Run <info>php acmephp.phar request '.$domain.'</info> to request this domain certificate for the first time.');

            return 1;
        }

        if (!$this->repository->hasDomainDistinguishedName($domain) || !$this->repository->hasDomainKeyPair($domain)) {
            if (!$this->repository->hasDomainDistinguishedName($domain)) {
                $this->output->writeln('<error>This domain distinguished name was not found.</error>');
            } else {
                $this->output->writeln('<error>This domain private key was not found.</error>');
            }

            $this->output->writeln('However, the domain current certificate was found which probably indicates a corruption in your domain data.');
            $this->output->writeln('To fix this, remove the directories ~/.acmephp/master/certs/'.$domain.' and ~/.acmephp/master/private/'.$domain);
            $this->output->writeln('to reset the domain certificate data and re-run <info>php acmephp.phar request '.$domain.'</info> to request a completely new certificate.');

            return 1;
        }

        /** @var LoggerInterface $monitoringLogger */

        try {
            // Check expiration date to avoid too much renewal
            $certificate = $this->repository->loadDomainCertificate($domain);

            if (!$this->input->getOption('force')) {
                $parsedCertificate = $this->certificateParser->parse($certificate);

                if ($parsedCertificate->getValidTo()->format('U') - time() >= 604800) {
                    $monitoringLogger->debug('Certificate does not need renewal', [
                        'domain'      => $domain,
                        'valid_until' => $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                    ]);

                    $this->output->writeln(sprintf(
                        '<info>Current certificate is valid until %s, renewal is not necessary. Use --force to force renewal.</info>',
                        $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'))
                    );

                    return 0;
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
