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

use AcmePhp\Cli\Application;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DomainConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('acmephp');
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $rootNode = $treeBuilder->root('acmephp');
        }

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($conf) {
                    return isset($conf['defaults']);
                })
                ->then(function ($conf) {
                    foreach ($conf['certificates'] as &$domainConf) {
                        $domainConf = $this->mergeArray((array) $domainConf, $conf['defaults']);
                    }

                    return $conf;
                })
            ->end()
            ->children()
                ->scalarNode('contact_email')
                    ->info('Email Address.')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(function ($item) {
                            return !filter_var($item, FILTER_VALIDATE_EMAIL);
                        })
                        ->thenInvalid('The email %s is not valid.')
                    ->end()
                ->end()
                ->scalarNode('provider')
                    ->info('Certificate provider to use (supported: '.implode(', ', Application::PROVIDERS).')')
                    ->defaultValue('letsencrypt')
                    ->validate()
                        ->ifTrue(function ($item) {
                            return !isset(Application::PROVIDERS[$item]);
                        })
                        ->thenInvalid('The certificate provider %s is not valid (supported: '.implode(', ', Application::PROVIDERS).').')
                    ->end()
                ->end()
                ->scalarNode('zerossl_api_key')
                    ->info('ZeroSSL API key to use if you already have one (one will be created automatically otherwise)')
                    ->defaultNull()
                ->end()
                ->scalarNode('key_type')
                    ->info('Type of private key (RSA or EC).')
                    ->defaultValue('RSA')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($conf) {
                            return strtoupper($conf);
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(function ($item) {
                            return !\in_array($item, ['RSA', 'EC']);
                        })
                        ->thenInvalid('The keyType %s is not valid. Supported types are: RSA, EC')
                    ->end()
                ->end()
            ->end()
            ->append($this->createDefaultsSection())
            ->append($this->createCertificatesSection());

        return $treeBuilder;
    }

    private function createDefaultsSection()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = (new TreeBuilder('defaults'))->getRootNode();
        } else {
            $rootNode = (new TreeBuilder())->root('defaults');
        }

        $rootNode
            ->info('Default configurations overridable by each certificate section.')
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifTrue(function ($conf) {
                    return isset($conf['solver']) && !\is_array($conf['solver']);
                })
                ->then(function ($conf) {
                    $conf['solver'] = ['name' => $conf['solver']];

                    return $conf;
                })
            ->end()
            ->append($this->createSolverSection())
            ->append($this->createDistinguishedNameSection());

        return $rootNode;
    }

    private function createSolverSection()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = (new TreeBuilder('solver'))->getRootNode();
        } else {
            $rootNode = (new TreeBuilder())->root('solver');
        }

        return $rootNode
            ->info('Challenge\'s solver configuration.')
            ->prototype('scalar')->end()
            ->requiresAtLeastOneElement()
            ->validate()
                ->ifTrue(function ($item) {
                    return !isset($item['name']);
                })
                ->thenInvalid('The name attribute %s is required in install property.')
            ->end();
    }

    private function createDistinguishedNameSection()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = (new TreeBuilder('distinguished_name'))->getRootNode();
        } else {
            $rootNode = (new TreeBuilder())->root('distinguished_name');
        }

        return $rootNode
            ->info('Distinguished Name (or a DN) informations.')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('country')
                    ->info('Country Name (2 letter code).')
                    ->defaultValue(null)
                    ->validate()
                        ->ifTrue(function ($item) {
                            return 2 !== \strlen($item);
                        })
                        ->thenInvalid('The country code %s is not valid.')
                    ->end()
                ->end()
                ->scalarNode('state')
                    ->info('State or Province Name (full name).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('locality')
                    ->info('Locality Name (eg, city).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('organization_name')
                    ->info('Organization Name (eg, company).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('organization_unit_name')
                    ->info('Organizational Unit Name (eg, section).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('email_address')
                    ->info('Email Address (eg, it@company.com).')
                    ->defaultValue(null)
                    ->validate()
                        ->ifTrue(function ($item) {
                            return !filter_var($item, FILTER_VALIDATE_EMAIL);
                        })
                        ->thenInvalid('The email %s is not valid.')
                    ->end()
                ->end()
            ->end();
    }

    private function createCertificatesSection()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $rootNode = (new TreeBuilder('certificates'))->getRootNode();
        } else {
            $rootNode = (new TreeBuilder())->root('certificates');
        }

        return $rootNode
            ->prototype('array')
                ->children()
                    ->scalarNode('domain')
                        ->info('Subject of the certificate.')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->arrayNode('subject_alternative_names')
                        ->info('Alternative subject names.')
                        ->requiresAtLeastOneElement()
                        ->normalizeKeys(false)
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('install')
                        ->info('install scripts.')
                        ->requiresAtLeastOneElement()
                        ->normalizeKeys(false)
                        ->prototype('array')
                            ->prototype('scalar')->end()
                            ->requiresAtLeastOneElement()
                            ->validate()
                                ->ifTrue(function ($item) {
                                    return !isset($item['action']);
                                })
                                ->thenInvalid('The action attribute %s is required in install property.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->ifTrue(function ($conf) {
                        return isset($conf['solver']) && !\is_array($conf['solver']);
                    })
                    ->then(function ($conf) {
                        $conf['solver'] = ['name' => $conf['solver']];

                        return $conf;
                    })
                ->end()
                ->append($this->createSolverSection())
                ->append($this->createDistinguishedNameSection())
            ->end();
    }

    private function mergeArray(array $array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (!isset($array1[$key])) {
                $array1[$key] = $value;
                continue;
            }

            if (!\is_array($value)) {
                continue;
            }
            if (!\is_array($array1[$key])) {
                throw new \Exception('Trying to merge incompatible array');
            }

            if (empty($value)) {
                continue;
            }
            if (isset($array2[0])) {
                continue;
            }

            $array1[$key] = $this->mergeArray($array1[$key], $value);
        }

        return $array1;
    }
}
