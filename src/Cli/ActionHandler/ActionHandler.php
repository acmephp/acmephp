<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\ActionHandler;

use AcmePhp\Cli\Exception\AcmeCliActionException;
use AcmePhp\Cli\Exception\AcmeCliException;
use AcmePhp\Ssl\CertificateResponse;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ActionHandler
{
    /**
     * @var ContainerInterface
     */
    private $actionLocator;

    /**
     * @var LoggerInterface
     */
    private $cliLogger;

    /**
     * @var array
     */
    private $postGenerateConfig;

    public function __construct(ContainerInterface $actionLocator, LoggerInterface $cliLogger, array $postGenerateConfig)
    {
        $this->actionLocator = $actionLocator;
        $this->cliLogger = $cliLogger;
        $this->postGenerateConfig = $postGenerateConfig;
    }

    /**
     * Apply all the registered actions to the given certificate response.
     *
     * @throws AcmeCliException       if the configuration is invalid
     * @throws AcmeCliActionException if there is a problem during the execution of an action
     */
    public function handle(CertificateResponse $response)
    {
        $actions = [];

        // Prepare
        foreach ($this->postGenerateConfig as $key => $actionConfig) {
            if (empty($actionConfig['action'])) {
                throw new AcmeCliException(sprintf('No action was configured at key storage.post_generate.%s, a non-empty "action" key is required.', $key));
            }

            $name = $actionConfig['action'];
            unset($actionConfig['action']);

            $actions[] = [
                'handler' => $this->actionLocator->get($name),
                'name' => $name,
                'config' => $actionConfig,
            ];
        }

        // Handle
        foreach ($actions as $action) {
            try {
                $this->cliLogger->info(' - Running '.$action['name'].'...');
                $action['handler']->handle($action['config'], $response);
            } catch (\Exception $exception) {
                throw new AcmeCliActionException($action['name'], $exception);
            }
        }
    }
}
