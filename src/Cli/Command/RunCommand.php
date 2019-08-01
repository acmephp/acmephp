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

use AcmePhp\Cli\Command\Helper\KeyOptionCommandTrait;
use AcmePhp\Cli\Configuration\DomainConfiguration;
use AcmePhp\Core\Challenge\ConfigurableServiceInterface;
use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Exception\Server\MalformedServerException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Generator\KeyOption;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\ParsedCertificate;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class RunCommand extends AbstractCommand
{
    use KeyOptionCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('run')
            ->setDefinition(
                [
                    new InputArgument('config', InputArgument::REQUIRED, 'path to the config file'),
                    new InputOption(
                        'delay',
                        'd',
                        InputOption::VALUE_REQUIRED,
                        'Time to live of certificate (in days) before forcing the renewal',
                        30
                    ),
                ]
            )
            ->setDescription('Automaticaly chalenge domain and request certificates configured in the given file')
            ->setHelp(
                <<<'EOF'
                The <info>%command.name%</info> challenge the domains, request the certificates and install them following a given configuration.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig(Path::makeAbsolute($input->getArgument('config'), getcwd()));
        $keyOption = $this->createKeyOption($config['key_type']);

        $this->register($config['contact_email'], $keyOption);
        foreach ($config['certificates'] as $domainConfig) {
            $domain = $domainConfig['domain'];
            $repository = $this->getRepository();

            if ($this->isUpToDate($domain, $domainConfig, (int) $input->getOption('delay'))) {
                $certificate = $this->getRepository()->loadDomainCertificate($domain);
                /** @var ParsedCertificate $parsedCertificate */
                $parsedCertificate = $this->getContainer()->get('ssl.certificate_parser')->parse($certificate);
                $this->output->writeln(sprintf('<info>Current certificate is valid until %s, renewal is not necessary. Change --delay parameter to force renewal.</info>', $parsedCertificate->getValidTo()->format(\DateTime::RSS)));

                $response = new CertificateResponse(
                    new CertificateRequest(
                        $repository->loadDomainDistinguishedName($domain),
                        $repository->loadDomainKeyPair($domain)
                    ),
                    $certificate
                );
            } else {
                $order = $this->challengeDomains($domainConfig, $keyOption);
                $this->requestCertificate($order, $domainConfig, $keyOption);

                $certificate = $this->getRepository()->loadDomainCertificate($domain);
                $response = new CertificateResponse(
                    new CertificateRequest(
                        $repository->loadDomainDistinguishedName($domain),
                        $repository->loadDomainKeyPair($domain)
                    ),
                    $certificate
                );
            }

            $this->installCertificate($domain, $response, $domainConfig['install']);
        }
    }

    private function register($email, KeyOption $keyOption)
    {
        $this->output->writeln(
            sprintf(
                '<comment>Registering contact %s...</comment>',
                $email
            )
        );

        $repository = $this->getRepository();
        if (!$repository->hasAccountKeyPair()) {
            $this->output->writeln('<info>No account key pair was found, generating one...</info>');

            /** @var KeyPair $accountKeyPair */
            $accountKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair($keyOption);

            $repository->storeAccountKeyPair($accountKeyPair);
        }

        $client = $this->getClient();
        $this->output->writeln('<info>Registering on the ACME server...</info>');

        try {
            $client->registerAccount(null, $email);
            $this->output->writeln('<info>Account registered successfully!</info>');
        } catch (MalformedServerException $e) {
            $this->output->writeln('<info>Account already registered!</info>');
        }
    }

    private function installCertificate($domain, CertificateResponse $response, array $actions)
    {
        $this->output->writeln(
            sprintf(
                '<comment>Installing certificate for domain %s...</comment>',
                $domain
            )
        );

        foreach ($actions as $actionConfig) {
            $handler = $this->getContainer()->get('acmephp.action.locator')->get($actionConfig['action']);
            $handler->handle($actionConfig, $response);

            $this->output->writeln(
                sprintf(
                    '<info>Certificate installed with the action %s.</info>',
                    $actionConfig['action']
                )
            );
        }
    }

    private function isUpToDate($domain, $domainConfig, $delay)
    {
        $repository = $this->getRepository();

        if (!$repository->hasDomainDistinguishedName($domain)) {
            return false;
        }

        $distinguishedName = $repository->loadDomainDistinguishedName($domain);
        $wantedCertificates = array_values(
            array_unique(array_merge([$domain], $domainConfig['subject_alternative_names']))
        );
        $requestedCertificates = array_values(
            array_unique(
                array_merge([$distinguishedName->getCommonName()], $distinguishedName->getSubjectAlternativeNames())
            )
        );
        if ($wantedCertificates !== $requestedCertificates) {
            return false;
        }

        if (!$repository->hasDomainCertificate($domain)) {
            return false;
        }

        $certificate = $repository->loadDomainCertificate($domain);
        /** @var ParsedCertificate $parsedCertificate */
        $parsedCertificate = $this->getContainer()->get('ssl.certificate_parser')->parse($certificate);
        if ($parsedCertificate->getValidTo() < new \DateTime(sprintf('%d days', $delay))) {
            return false;
        }

        return true;
    }

    private function requestCertificate(CertificateOrder $order, $domainConfig, KeyOption $keyOption)
    {
        $domain = $domainConfig['domain'];
        $this->output->writeln(sprintf('<comment>Requesting certificate for domain %s...</comment>', $domain));

        $repository = $this->getRepository();
        $client = $this->getClient();
        $csr = null;
        if (!$client->isCsrEager()) {
            $distinguishedName = new DistinguishedName(
                $domainConfig['domain'],
                $domainConfig['distinguished_name']['country'],
                $domainConfig['distinguished_name']['state'],
                $domainConfig['distinguished_name']['locality'],
                $domainConfig['distinguished_name']['organization_name'],
                $domainConfig['distinguished_name']['organization_unit_name'],
                $domainConfig['distinguished_name']['email_address'],
                $domainConfig['subject_alternative_names']
            );

            if ($repository->hasDomainKeyPair($domain)) {
                $domainKeyPair = $repository->loadDomainKeyPair($domain);
            } else {
                $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair($keyOption);
                $repository->storeDomainKeyPair($domain, $domainKeyPair);
            }

            $repository->storeDomainDistinguishedName($domain, $distinguishedName);

            $csr = new CertificateRequest($distinguishedName, $domainKeyPair);
        }
        $response = $client->finalizeOrder($order, $csr);

        $this->output->writeln('<info>Certificate requested successfully!</info>');

        $repository->storeCertificateResponse($domain, $response);

        return $response;
    }

    private function challengeDomains(array $domainConfig, KeyOption $keyOption)
    {
        $solverConfig = $domainConfig['solver'];
        $domain = $domainConfig['domain'];

        $solverLocator = $this->getContainer()->get('acmephp.challenge_solver.locator');
        /** @var SolverInterface $solver */
        $solver = $solverLocator->get($solverConfig['name']);
        if ($solver instanceof ConfigurableServiceInterface) {
            $solver->configure($solverConfig);
        }

        /** @var ValidatorInterface $validator */
        $validator = $this->getContainer()->get('challenge_validator');

        $client = $this->getClient();
        $domains = array_unique(array_merge([$domain], $domainConfig['subject_alternative_names']));

        $csr = null;
        if ($client->isCsrEager()) {
            $domain = $domainConfig['domain'];
            $this->output->writeln(sprintf('<comment>Requesting certificate for domain %s...</comment>', $domain));

            $repository = $this->getRepository();
            $distinguishedName = new DistinguishedName(
                $domainConfig['domain'],
                $domainConfig['distinguished_name']['country'],
                $domainConfig['distinguished_name']['state'],
                $domainConfig['distinguished_name']['locality'],
                $domainConfig['distinguished_name']['organization_name'],
                $domainConfig['distinguished_name']['organization_unit_name'],
                $domainConfig['distinguished_name']['email_address'],
                $domainConfig['subject_alternative_names']
            );

            if ($repository->hasDomainKeyPair($domain)) {
                $domainKeyPair = $repository->loadDomainKeyPair($domain);
            } else {
                $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair($keyOption);
                $repository->storeDomainKeyPair($domain, $domainKeyPair);
            }

            $repository->storeDomainDistinguishedName($domain, $distinguishedName);

            $csr = new CertificateRequest($distinguishedName, $domainKeyPair);

            $this->output->writeln('<comment>Requesting certificate order...</comment>');
        }
        $order = $client->requestOrder($domains, $csr);

        $authorizationChallengesToSolve = [];
        foreach ($order->getAuthorizationsChallenges() as $domain => $authorizationChallenges) {
            /* @var AuthorizationChallenge $candidate */
            foreach ($authorizationChallenges as $authorizationChallenge) {
                if ($authorizationChallenge->isValid()) {
                    $this->output->writeln(sprintf('<info>Authorization already validated for domain %s...</info>', $domain));
                    continue 2;
                }
            }

            foreach ($authorizationChallenges as $authorizationChallenge) {
                if ($authorizationChallenge->isPending() && $solver->supports($authorizationChallenge)) {
                    $authorizationChallengesToSolve[$domain] = $authorizationChallenge;
                    continue 2;
                }
            }

            throw new ChallengeNotSupportedException();
        }

        if ($solver instanceof MultipleChallengesSolverInterface) {
            $this->output->writeln('<info>Solving challenge for domains...</info>');
            $solver->solveAll($authorizationChallengesToSolve);
        } else {
            /** @var AuthorizationChallenge $authorizationChallenge */
            foreach ($authorizationChallengesToSolve as $domain => $authorizationChallenge) {
                $this->output->writeln(sprintf('<info>Solving challenge for domain %s...</info>', $domain));
                $solver->solve($authorizationChallenge);
            }
        }

        $startTestTime = time();
        foreach ($authorizationChallengesToSolve as $domain => $authorizationChallenge) {
            if ($authorizationChallenge->isValid()) {
                continue;
            }

            $this->output->writeln(sprintf('<info>Testing the challenge for domain %s...</info>', $domain));
            if (time() - $startTestTime > 180 || !$validator->isValid($authorizationChallenge)) {
                $this->output->writeln(sprintf('<info>Can not self validate challenge for domain %s. Maybe letsencrypt will be able to do it...</info>', $domain));
            }

            $this->output->writeln(sprintf('<info>Requesting authorization check for domain %s...</info>', $domain));
            $client->challengeAuthorization($authorizationChallenge);
        }

        if ($solver instanceof MultipleChallengesSolverInterface) {
            $this->output->writeln('<info>Cleaning up challenges for domains...</info>');
            $solver->cleanupAll($authorizationChallengesToSolve);
        } else {
            /** @var AuthorizationChallenge $authorizationChallenge */
            foreach ($authorizationChallengesToSolve as $domain => $authorizationChallenge) {
                $this->output->writeln(sprintf('<info>Cleaning up challenge for domain %s...</info>', $domain));
                $solver->cleanup($authorizationChallenge);
            }
        }

        return $order;
    }

    private function getConfig($configFile)
    {
        return $this->resolveConfig(
            $this->loadConfig($configFile)
        );
    }

    private function loadConfig($configFile)
    {
        if (!file_exists($configFile)) {
            throw new IOException('Configuration file '.$configFile.' does not exists.');
        }

        if (!is_readable($configFile)) {
            throw new IOException('Configuration file '.$configFile.' is not readable.');
        }

        return Yaml::parse(file_get_contents($configFile));
    }

    private function resolveConfig($config)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new DomainConfiguration(), ['acmephp' => $config]);
    }
}
