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
use AcmePhp\Core\AcmeClientV2Interface;
use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\CertificateRevocationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RevokeCommand extends AbstractCommand
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
        $this->setName('revoke')
            ->setDefinition([
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain revoke a certificate for'),
                new InputArgument('reason-code',  InputOption::VALUE_OPTIONAL, 'The reason code for revocation'),
            ])
            ->setDescription('Revoke a SSL certificate for a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command revoke a previously obtained certificate for a given domain
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

        $domain = (string)$input->getArgument('domain');
        $reasonCode = (int)$input->getArgument('reason-code');

        if (!$this->repository->hasDomainCertificate($domain)) {
            $this->error("Certificate for {$domain} not found locally");
            return;
        }

        $certificate = $this->repository->loadDomainCertificate($domain);

        try {
            $this->client->revokeCertificate($certificate, $reasonCode);
        } catch (CertificateRevocationException $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->notice('Certificate revoked successfully!');
    }
}
