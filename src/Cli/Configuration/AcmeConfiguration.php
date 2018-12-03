<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('acmephp');
        if (\method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $rootNode = $treeBuilder->root('acmephp');
        }

        $this->createRootNode($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    protected function createRootNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('storage')
                    ->info('Configure here where and how you want to save your certificates and SSL keys.')
                    ->children()
                        ->booleanNode('enable_backup')
                            ->info('By default, Acme PHP will create a backup of every file before any modification. You can disable this mechanism here.')
                            ->isRequired()
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('post_generate')
                            ->info('Actions to execute right after the generation of a file (key, CSR or certificate). Actions are executed in the order provided in configuration.')
                            ->normalizeKeys(false)
                            ->prototype('variable')
                                ->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function ($action) {
                                    return !array_key_exists('action', $action);
                                })
                                    ->thenInvalid('The "action" configuration key is required.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('monitoring')
                    ->info('Configure here a simple monitoring mechanism that will warn you if an error occurs during a CRON job.')
                    ->normalizeKeys(false)
                    ->prototype('variable')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();
    }
}
