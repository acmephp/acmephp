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
use Monolog\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class MonitoringLoggerFactory
{
    private $container;
    private $monitoringConfig;

    private static $levels = [
        'info' => Logger::INFO,
        'error' => Logger::ERROR,
    ];

    public function __construct(ContainerInterface $container, array $monitoringConfig)
    {
        $this->container = $container;
        $this->monitoringConfig = $monitoringConfig;
    }

    public function createLogger()
    {
        if (!$this->monitoringConfig) {
            return new NullLogger();
        }

        $logger = new Logger('acmephp');

        foreach ($this->monitoringConfig as $name => $config) {
            if (!$this->container->has('monitoring.'.$name)) {
                throw new AcmeCliException(sprintf('Monitoring handler %s does not exists.', $name));
            }

            if (isset($config['level'])) {
                if (!isset(self::$levels[$config['level']])) {
                    throw new AcmeCliException(sprintf('Monitoring handler level "%s" is not valid.', $config['level']));
                }

                $config['level'] = self::$levels[$config['level']];
            } else {
                $config['level'] = null;
            }

            $logger->pushHandler($this->container->get('monitoring.'.$name)->createHandler($config));
        }

        return $logger;
    }
}
