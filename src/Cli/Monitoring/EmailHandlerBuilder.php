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
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class EmailHandlerBuilder implements HandlerBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function createHandler($config)
    {
        if (!isset($config['host'])) {
            throw new AcmeCliException('The SMTP host (key "host") is required in the email monitoring alert handler.');
        }

        if (!isset($config['to'])) {
            throw new AcmeCliException('The mail recipient (key "to") is required in the email monitoring alert handler.');
        }

        $from = isset($config['from']) ? $config['from'] : 'monitoring@acmephp.github.io';
        $subject = isset($config['subject']) ? $config['subject'] : 'An error occured during Acme PHP CRON renewal';
        $port = isset($config['port']) ? $config['port'] : 25;
        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
        $encryption = isset($config['encryption']) ? $config['encryption'] : null;

        $transport = new \Swift_SmtpTransport($config['host'], $port, $encryption);

        if ($username) {
            $transport->setUsername($username);
        }

        if ($password) {
            $transport->setPassword($password);
        }

        $message = new \Swift_Message($subject);
        $message->setFrom($from);

        $handler = new SwiftMailerHandler(new \Swift_Mailer($transport), $message);

        // By default, alert only for errors
        return new FingersCrossedHandler($handler, $config['level'] ?: Logger::ERROR);
    }
}
