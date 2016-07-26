<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Monitoring;

use AcmePhp\Cli\Exception\AcmeCliException;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Logger;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SlackHandlerBuilder implements HandlerBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function createHandler($config)
    {
        if (!isset($config['token'])) {
            throw new AcmeCliException('The Slack token (key "token") is required in the slack monitoring alert handler.');
        }

        if (!isset($config['channel'])) {
            throw new AcmeCliException('The Slack channel (key "channel") is required in the slack monitoring alert handler.');
        }

        $username = isset($config['username']) ? $config['username'] : 'Acme PHP';

        $handler = new SlackHandler($config['token'], '#'.ltrim($config['channel'], '#'), $username, true, null, Logger::DEBUG);

        // By default, alert on every time
        return new FingersCrossedHandler($handler, $config['level'] ?: Logger::INFO);
    }
}
