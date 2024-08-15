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

use AcmePhp\Cli\Application;
use AcmePhp\Core\Exception\Protocol\CertificateRevocationException;
use AcmePhp\Core\Protocol\RevocationReason;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RevokeCommand extends AbstractCommand
{
    protected function configure()
    {
        $reasons = implode(PHP_EOL, RevocationReason::getFormattedReasons());

        $this->setName('revoke')
            ->setDefinition(array(
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain revoke a certificate for'),
                new InputArgument('reason-code', InputOption::VALUE_OPTIONAL, 'The reason code for revocation:' . PHP_EOL . $reasons),
                new InputOption(
                    'provider',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Certificate provider to use (supported: ' . implode(', ', Application::PROVIDERS) . ')',
                    'letsencrypt'
                ),
            ))
            ->setDescription('Revoke a SSL certificate for a domain')
            ->setHelp('The <info>%command.name%</info> command revoke a previously obtained certificate for a given domain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!isset(Application::PROVIDERS[$this->input->getOption('provider')])) {
            throw new \InvalidArgumentException('Invalid provider, supported: ' . implode(', ', Application::PROVIDERS));
        }

        $repository = $this->getRepository();
        $client = $this->getClient(Application::PROVIDERS[$this->input->getOption('provider')]);

        $domain = (string) $input->getArgument('domain');
        $reasonCode = $input->getArgument('reason-code'); // ok to be null. LE expects 0 as default reason

        try {
            $revocationReason = isset($reasonCode[0]) ? new RevocationReason($reasonCode[0]) : RevocationReason::createDefaultReason();
        } catch (\InvalidArgumentException $e) {
            $this->error('Reason code must be one of: ' . PHP_EOL . implode(PHP_EOL, RevocationReason::getFormattedReasons()));

            return;
        }

        if (!$repository->hasDomainCertificate($domain)) {
            $this->error('Certificate for ' . $domain . ' not found locally');

            return;
        }

        $certificate = $repository->loadDomainCertificate($domain);

        try {
            $client->revokeCertificate($certificate, $revocationReason);
        } catch (CertificateRevocationException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->notice('Certificate revoked successfully!');

        return 0;
    }
}
