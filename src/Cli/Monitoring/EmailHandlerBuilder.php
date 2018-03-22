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
    private static $defaults = [
        'from' => 'monitoring@acmephp.github.io',
        'subject' => 'An error occured during Acme PHP CRON renewal',
        'port' => 25,
        'username' => null,
        'password' => null,
        'encryption' => null,
        'level' => Logger::ERROR,
    ];

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

        $config = array_merge(self::$defaults, $config);

        $transport = new \Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']);

        if ($config['username']) {
            $transport->setUsername($config['username']);
        }

        if ($config['password']) {
            $transport->setPassword($config['password']);
        }

        $message = new \Swift_Message($config['subject']);
        $message->setFrom($config['from']);
        $message->setTo($config['to']);

        $handler = new SwiftMailerHandler(new \Swift_Mailer($transport), $message);

        return new FingersCrossedHandler($handler, $config['level']);
    }
}
