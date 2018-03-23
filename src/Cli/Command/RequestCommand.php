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
use AcmePhp\Cli\Repository\Repository;
use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Core\AcmeClientV2Interface;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
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
     * @var AcmeClientV2Interface
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
                new InputOption('alternative-name', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Alternative domains for this certificate'),
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
        $alternativeNames = array_unique($input->getOption('alternative-name'));
        sort($alternativeNames);

        // Certificate renewal
        if ($this->hasValidCertificate($domain, $alternativeNames)) {
            $this->debug('Certificate found, executing renewal', [
                'domain' => $domain,
                'alternative_names' => $alternativeNames,
            ]);

            return $this->executeRenewal($domain, $alternativeNames);
        }

        $this->debug('No certificate found, executing first request', [
            'domain' => $domain,
            'alternative_names' => $alternativeNames,
        ]);

        // Certificate first request
        return $this->executeFirstRequest($domain, $alternativeNames);
    }

    private function hasValidCertificate($domain, array $alternativeNames)
    {
        if (!$this->repository->hasDomainCertificate($domain)) {
            return false;
        }

        if (!$this->repository->hasDomainKeyPair($domain)) {
            return false;
        }

        if (!$this->repository->hasDomainDistinguishedName($domain)) {
            return false;
        }

        if ($this->repository->loadDomainDistinguishedName($domain)->getSubjectAlternativeNames() !== $alternativeNames) {
            return false;
        }

        return true;
    }

    /**
     * Request a first certificate for the given domain.
     *
     * @param string $domain
     * @param array  $alternativeNames
     */
    private function executeFirstRequest($domain, array $alternativeNames)
    {
        $introduction = <<<'EOF'

There is currently no certificate for domain %s in the Acme PHP storage. As it is the
first time you request a certificate for this domain, some configuration is required.
 
<info>Generating domain key pair...</info>
EOF;

        $this->info(sprintf($introduction, $domain));

        // Generate domain key pair
        $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair();
        $this->repository->storeDomainKeyPair($domain, $domainKeyPair);

        $this->debug('Domain key pair generated and stored', [
            'domain' => $domain,
            'public_key' => $domainKeyPair->getPublicKey()->getPEM(),
        ]);

        $distinguishedName = $this->getOrCreateDistinguishedName($domain, $alternativeNames);
        $this->notice('Distinguished name informations have been stored locally for this domain (they won\'t be asked on renewal).');

        // Order
        $domains = array_merge([$domain], $alternativeNames);
        $this->notice(sprintf('Loading the order related to the domains %s ...', implode(', ', $domains)));
        $order = $this->getRepository()->loadCertificateOrder($domains);

        // Request
        $this->notice(sprintf('Requesting first certificate for domain %s ...', $domain));
        $csr = new CertificateRequest($distinguishedName, $domainKeyPair);
        $response = $this->client->finalizeOrder($order, $csr);
        $this->debug('Certificate received', ['certificate' => $response->getCertificate()->getPEM()]);

        // Store
        $this->repository->storeDomainCertificate($domain, $response->getCertificate());
        $this->debug('Certificate stored');

        // Post-generate actions
        $this->notice('Running post-generate actions...');
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
            '%expiration%' => $parsedCertificate->getValidTo()->format(\DateTime::ISO8601),
            '%private%' => $masterPath.'/'.Repository::PATH_DOMAIN_KEY_PRIVATE,
            '%combined%' => $masterPath.'/'.Repository::PATH_DOMAIN_CERT_COMBINED,
            '%cert%' => $masterPath.'/'.Repository::PATH_DOMAIN_CERT_CERT,
            '%chain%' => $masterPath.'/'.Repository::PATH_DOMAIN_CERT_CHAIN,
            '%fullchain%' => $masterPath.'/'.Repository::PATH_DOMAIN_CERT_FULLCHAIN,
        ];

        $this->info(strtr(strtr($success, $replacements), ['{domain}' => $domain]));
    }

    /**
     * Renew a given domain certificate.
     *
     * @param string $domain
     * @param array  $alternativeNames
     */
    private function executeRenewal($domain, array $alternativeNames)
    {
        /** @var LoggerInterface $monitoringLogger */
        $monitoringLogger = $this->getContainer()->get('acmephp.monitoring_factory')->createLogger();

        try {
            // Check expiration date to avoid too much renewal
            $this->debug('Loading current certificate', [
                'domain' => $domain,
            ]);

            $certificate = $this->repository->loadDomainCertificate($domain);

            if (!$this->input->getOption('force')) {
                /** @var ParsedCertificate $parsedCertificate */
                $parsedCertificate = $this->getContainer()->get('ssl.certificate_parser')->parse($certificate);

                if ($parsedCertificate->getValidTo()->format('U') - time() >= 604800) {
                    $monitoringLogger->debug('Certificate does not need renewal', [
                        'domain' => $domain,
                        'valid_until' => $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                    ]);

                    $this->notice(sprintf(
                        'Current certificate is valid until %s, renewal is not necessary. Use --force to force renewal.',
                        $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'))
                    );

                    // Post-generate actions
                    $this->info('Running post-generate actions...');
                    $response = new CertificateResponse(
                        new CertificateRequest(
                            $this->repository->loadDomainDistinguishedName($domain),
                            $this->repository->loadDomainKeyPair($domain)
                        ),
                        $certificate
                    );

                    $this->actionHandler->handle($response);

                    return;
                }

                $monitoringLogger->debug('Certificate needs renewal', [
                    'domain' => $domain,
                    'valid_until' => $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                ]);

                $this->notice(sprintf(
                    'Current certificate will expire in less than a week (%s), renewal is required.',
                    $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'))
                );
            } else {
                $this->notice('Forced renewal.');
            }

            // Key pair
            $this->info('Loading domain key pair...');
            $domainKeyPair = $this->repository->loadDomainKeyPair($domain);

            // Distinguished name
            $this->info('Loading domain distinguished name...');
            $distinguishedName = $this->getOrCreateDistinguishedName($domain, $alternativeNames);

            // Order
            $domains = array_merge([$domain], $alternativeNames);
            $this->notice(sprintf('Loading the order related to the domains %s ...', implode(', ', $domains)));
            $order = $this->getRepository()->loadCertificateOrder($domains);

            // Renewal
            $this->info(sprintf('Renewing certificate for domain %s ...', $domain));
            $csr = new CertificateRequest($distinguishedName, $domainKeyPair);
            $response = $this->client->finalizeOrder($order, $csr);
            $this->debug('Certificate received', ['certificate' => $response->getCertificate()->getPEM()]);

            $this->repository->storeDomainCertificate($domain, $response->getCertificate());
            $this->debug('Certificate stored');

            // Post-generate actions
            $this->info('Running post-generate actions...');
            $this->actionHandler->handle($response);

            $this->notice('Certificate renewed successfully!');

            $monitoringLogger->info('Certificate renewed successfully', ['domain' => $domain]);
        } catch (\Exception $e) {
            $monitoringLogger->alert('A critical error occured during certificate renewal', ['exception' => $e]);

            throw $e;
        } catch (\Throwable $e) {
            $monitoringLogger->alert('A critical error occured during certificate renewal', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Retrieve the stored distinguishedName or create a new one if needed.
     *
     * @param string $domain
     * @param array  $alternativeNames
     *
     * @return DistinguishedName
     */
    private function getOrCreateDistinguishedName($domain, array $alternativeNames)
    {
        if ($this->repository->hasDomainDistinguishedName($domain)) {
            $original = $this->repository->loadDomainDistinguishedName($domain);

            $distinguishedName = new DistinguishedName(
                $domain,
                $this->input->getOption('country') ?: $original->getCountryName(),
                $this->input->getOption('province') ?: $original->getStateOrProvinceName(),
                $this->input->getOption('locality') ?: $original->getLocalityName(),
                $this->input->getOption('organization') ?: $original->getOrganizationName(),
                $this->input->getOption('unit') ?: $original->getOrganizationalUnitName(),
                $this->input->getOption('email') ?: $original->getEmailAddress(),
                $alternativeNames
            );
        } else {
            // Ask DistinguishedName
            $distinguishedName = new DistinguishedName(
                $domain,
                $this->input->getOption('country'),
                $this->input->getOption('province'),
                $this->input->getOption('locality'),
                $this->input->getOption('organization'),
                $this->input->getOption('unit'),
                $this->input->getOption('email'),
                $alternativeNames
            );

            /** @var DistinguishedNameHelper $helper */
            $helper = $this->getHelper('distinguished_name');

            if (!$helper->isReadyForRequest($distinguishedName)) {
                $this->info("\n\nSome informations about you or your company are required for the certificate:\n");

                $distinguishedName = $helper->ask(
                    $this->getHelper('question'),
                    $this->input,
                    $this->output,
                    $distinguishedName
                );
            }
        }

        $this->repository->storeDomainDistinguishedName($domain, $distinguishedName);

        return $distinguishedName;
    }
}
