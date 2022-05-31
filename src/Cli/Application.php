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

use AcmePhp\Cli\Command\Helper\DistinguishedNameHelper;
use AcmePhp\Cli\Command\RevokeCommand;
use AcmePhp\Cli\Command\RunCommand;
use AcmePhp\Cli\Command\SelfUpdateCommand;
use AcmePhp\Cli\Command\StatusCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\HelperSet;
use Webmozart\PathUtil\Path;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Application extends BaseApplication
{
    public const PROVIDERS = [
        'letsencrypt' => 'https://acme-v02.api.letsencrypt.org/directory',
        'zerossl' => 'https://acme.zerossl.com/v2/DV90',
        'localhost' => 'https://localhost:14000/dir',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('Acme PHP - Let\'s Encrypt/ZeroSSL client', '2.0.0');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
    {
        return array_merge(parent::getDefaultCommands(), [
            new RunCommand(),
            new RevokeCommand(),
            new StatusCommand(),
            new SelfUpdateCommand(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet(): HelperSet
    {
        $set = parent::getDefaultHelperSet();
        $set->set(new DistinguishedNameHelper());

        return $set;
    }

    /**
     * @return string
     */
    public function getStorageDirectory()
    {
        return Path::canonicalize('~/.acmephp/master');
    }
}
