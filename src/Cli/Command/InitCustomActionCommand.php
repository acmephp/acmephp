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
class InitCustomActionCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('init-custom-action')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'The custom action name (the name you will use in your configuration, the equivalent of push_ftp or build_nginxproxy)'),
            ])
            ->setDescription('Generate a demo custom post-generate action')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command list will create a demo custom post-generate action in the <info>~/.acmephp/actions</info>
directory. You will be able to edit the generated file in order to implement a specific behavior to deal with your certificates.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
