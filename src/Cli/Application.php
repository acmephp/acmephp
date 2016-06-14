<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli;

use AcmePhp\Cli\Command\AuthorizeCommand;
use AcmePhp\Cli\Command\RegisterCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputOption;
use Webmozart\PathUtil\Path;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Application extends BaseApplication
{
    const VERSION = '1.0.0-alpha8';

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('Acme PHP - Let\'s Encrypt client', self::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return [
            new HelpCommand(),
            new ListCommand(),
            new RegisterCommand(),
            new AuthorizeCommand(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption(
            'server',
            null,
            InputOption::VALUE_REQUIRED,
            'Set the ACME server directory to use',
            'https://acme-v01.api.letsencrypt.org/directory'
        ));

        return $definition;
    }

    /**
     * @return string
     */
    public static function getConfigFile()
    {
        return Path::canonicalize('~/.acmephp/acmephp.conf');
    }

    /**
     * @return string
     */
    public static function getConfigReferenceFile()
    {
        return Path::canonicalize(__DIR__.'/../../res/acmephp.conf.dist');
    }

    /**
     * @return string
     */
    public static function getStorageDirectory()
    {
        return Path::canonicalize('~/.acmephp/master');
    }

    /**
     * @return string
     */
    public static function getBackupDirectory()
    {
        return Path::canonicalize('~/.acmephp/backup');
    }
}
