<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Action;

use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\AdapterInterface;

/**
 * Action to write files using a Flysystem adapter.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PushFtpAction extends AbstractFlysystemAction
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'push_ftp';
    }

    /**
     * @param array $config
     *
     * @return AdapterInterface
     */
    protected function createAdapter($config)
    {
        return new FtpAdapter($config);
    }

    /**
     * @param FtpAdapter $adapter
     *
     * @return string
     */
    protected function getLastError(AdapterInterface $adapter)
    {
        return error_get_last();
    }
}
