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

use AcmePhp\Ssl\CertificateResponse;

/**
 * Action to write files using a Flysystem adapter.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PushFtpAction implements ActionInterface
{
    /**
     * @var FilesystemAction
     */
    private $filesystemAction;

    /**
     * @param FilesystemAction
     */
    public function __construct(FilesystemAction $filesystemAction)
    {
        @trigger_error('The "push_ftp" action is deprecated since version 1.0 and will be removed in 2.0. Use "mirror_file" action instead', E_USER_DEPRECATED);

        $this->filesystemAction = $filesystemAction;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $config, CertificateResponse $response)
    {
        $config['adapter'] = 'ftp';

        $this->filesystemAction->handle($config, $response);
    }
}
