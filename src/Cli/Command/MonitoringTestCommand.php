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

use AcmePhp\Cli\Exception\AcmeCliException;
use AcmePhp\Cli\Monitoring\HandlerBuilderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class MonitoringTestCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('monitoring-test')
            ->setDefinition([
                new InputArgument('level', InputArgument::OPTIONAL, 'The level to use for the test (info/error, by default error)', 'error'),
            ])
            ->setDescription('Throw an error in a monitored context to test your configuration')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command list will set up the same monitored context as in your CRON
jobs and will voluntarily throw an error inside it so you can check you are successfully alerted if
there is a problem.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->info('Loading monitoring configuration...');

        /** @var LoggerInterface $monitoringLogger */
        $monitoringLogger = $this->getContainer()->get('acmephp.monitoring_factory')->createLogger();

        $level = $input->getArgument('level');

        if (!in_array($level, [HandlerBuilderInterface::LEVEL_ERROR, HandlerBuilderInterface::LEVEL_INFO], true)) {
            throw new AcmeCliException('Level '.$level.' is not valid (available levels: info, error)');
        }

        $this->info('Triggering monitoring on "'.$level.'" level...');

        if (HandlerBuilderInterface::LEVEL_INFO === $level) {
            $monitoringLogger->info('This is a testing message from Acme PHP monitoring (info level)');
        } else {
            $monitoringLogger->alert('This is a testing message from Acme PHP monitoring (error level)');
        }

        $this->notice('Triggered successfully');
        $this->info('You should have been alerted');
    }
}
