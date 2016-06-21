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

use League\Flysystem\AdapterInterface;
use League\Flysystem\Sftp\SftpAdapter;

/**
 * Action to write files using a Flysystem adapter.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PushSftpAction extends AbstractFlysystemAction
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'push_sftp';
    }

    /**
     * @param array $config
     *
     * @return AdapterInterface
     */
    protected function createAdapter($config)
    {
        if (isset($config['private_key'])) {
            $config['privateKey'] = $config['private_key'];
            unset($config['private_key']);
        }

        return new SftpAdapter($config);
    }

    /**
     * @param SftpAdapter $adapter
     *
     * @return string
     */
    protected function getLastError(AdapterInterface $adapter)
    {
        return $adapter->getConnection()->getLastSFTPError();
    }
}
