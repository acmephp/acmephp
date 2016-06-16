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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ActionHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $postGenerateConfig;

    /**
     * @param ContainerInterface $container
     * @param array              $postGenerateConfig
     */
    public function __construct(ContainerInterface $container, array $postGenerateConfig)
    {
        $this->container = $container;
        $this->postGenerateConfig = $postGenerateConfig;
    }

    /**
     * Apply all the registered actions to the given certificate response.
     *
     * @param CertificateResponse $response
     *
     * @throws AcmeCliException       If the configuration is invalid.
     * @throws AcmeCliActionException If there is a problem during the execution of an action.
     */
    public function handle(CertificateResponse $response)
    {
        $actions = [];

        // Prepare
        foreach ($this->postGenerateConfig as $key => $actionConfig) {
            if (empty($actionConfig['action'])) {
                throw new AcmeCliException(sprintf(
                    'No action was configured at key storage.post_generate.%s, a non-empty "action" key is required.',
                    $key
                ));
            }

            $name = $actionConfig['action'];
            unset($actionConfig['action']);

            if (!$this->container->has('action.'.$name)) {
                throw new AcmeCliException(sprintf(
                    'Action %s does not exists at key storage.post_generate.%s.',
                    $name,
                    $key
                ));
            }

            $actions[] = [
                'handler' => $this->container->get('action.'.$name),
                'name'    => $name,
                'config'  => $actionConfig,
            ];
        }

        // Handle
        foreach ($actions as $action) {
            try {
                $action['handler']->handle($action['config'], $response);
            } catch (\Exception $exception) {
                throw new AcmeCliActionException($action['name'], $exception);
            }
        }
    }
}
