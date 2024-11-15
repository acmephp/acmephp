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

use AcmePhp\Ssl\Parser\CertificateParser;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class StatusCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('status')
            ->setDescription('List all the certificates handled by Acme PHP')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'include expired certificates too')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command list all the certificates stored in the Acme PHP storage.
It also displays useful informations about these such as the certificate validity and issuer.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->getRepository();

        /** @var FilesystemOperator $master */
        $master = $this->getContainer()->get('repository.storage');

        /** @var CertificateParser $certificateParser */
        $certificateParser = $this->getContainer()->get('ssl.certificate_parser');

        $table = new Table($output);
        $table->setHeaders(['Domain', 'Issuer', 'Valid from', 'Valid to', 'Needs renewal?']);

        $directories = $master->listContents('certs');

        foreach ($directories as $directory) {
            if ('dir' !== $directory['type']) {
                continue;
            }

            $parsedCertificate = $certificateParser->parse($repository->loadDomainCertificate($directory['basename']));
            if (!$input->getOption('all') && $parsedCertificate->isExpired()) {
                continue;
            }
            $domainString = $parsedCertificate->getSubject();

            $alternativeNames = array_diff($parsedCertificate->getSubjectAlternativeNames(), [$parsedCertificate->getSubject()]);
            if (\count($alternativeNames)) {
                sort($alternativeNames);
                $last = array_pop($alternativeNames);
                foreach ($alternativeNames as $alternativeName) {
                    $domainString .= "\n ├── ".$alternativeName;
                }
                $domainString .= "\n └── ".$last;
            }

            $table->addRow([
                $domainString,
                $parsedCertificate->getIssuer(),
                $parsedCertificate->getValidFrom()->format('Y-m-d H:i:s'),
                $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                ($parsedCertificate->getValidTo()->format('U') - time() < 604800) ? '<comment>Yes</comment>' : 'No',
            ]);
        }

        $table->render();

        return 0;
    }
}
