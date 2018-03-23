<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command;

use AcmePhp\Cli\ActionHandler\ActionHandler;
use AcmePhp\Cli\Application;
use AcmePhp\Cli\Configuration\AcmeConfiguration;
use AcmePhp\Cli\Repository\RepositoryV2Interface;
use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\Challenge\Dns\LibDnsResolver;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractCommand extends Command implements LoggerInterface
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var null|array
     */
    private $configuration;

    /**
     * @var null|ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @return RepositoryV2Interface
     */
    protected function getRepository()
    {
        $this->debug('Loading repository');

        return $this->getContainer()->get('repository');
    }

    /**
     * @return ActionHandler
     */
    protected function getActionHandler()
    {
        $this->debug('Loading action handler');

        return $this->getContainer()->get('acmephp.action_handler');
    }

    /**
     * @return AcmeClient
     */
    protected function getClient()
    {
        $this->debug('Creating Acme client');
        $this->notice('Loading account key pair...');

        $accountKeyPair = $this->getRepository()->loadAccountKeyPair();

        /** @var SecureHttpClient $httpClient */
        $httpClient = $this->getContainer()->get('http.client_factory')->createSecureHttpClient($accountKeyPair);

        /** @var CertificateRequestSigner $csrSigner */
        $csrSigner = $this->getContainer()->get('ssl.csr_signer');

        return new AcmeClient($httpClient, $this->input->getOption('server'), $csrSigner);
    }

    /**
     * @return LoggerInterface
     */
    protected function getCliLogger()
    {
        return $this->getContainer()->get('cli_logger');
    }

    /**
     * @return ContainerBuilder
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $this->initializeContainer();
        }

        return $this->container;
    }

    private function initializeContainer()
    {
        if (null === $this->configuration) {
            $this->initializeConfiguration();
        }

        $this->container = new ContainerBuilder();

        // Application services and parameters
        $this->container->set('app', $this->getApplication());
        $this->container->set('container', $this->container);
        $this->container->setParameter('app.version', Application::VERSION);
        $this->container->setParameter('app.storage_directory', $this->getApplication()->getStorageDirectory());
        $this->container->setParameter('app.backup_directory', $this->getApplication()->getBackupDirectory());

        // Load configuration
        $processor = new Processor();
        $config = $processor->processConfiguration(new AcmeConfiguration(), $this->configuration);
        $this->container->setParameter('storage.enable_backup', $config['storage']['enable_backup']);
        $this->container->setParameter('storage.post_generate', $config['storage']['post_generate']);
        $this->container->setParameter('monitoring.handlers', $config['monitoring']);

        // Load services
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__.'/../Resources'));
        $loader->load('services.xml');

        foreach ($this->container->findTaggedServiceIds('acmephp.service_locator') as $locatorId => $locatorTags) {
            if (!isset($locatorTags[0]['tag'])) {
                throw new \InvalidArgumentException(
                    sprintf('The tagged service "%s" must define have an alias', $serviceId)
                );
            }
            $locatorTags = $locatorTags[0]['tag'];
            $factories = [];
            foreach ($this->container->findTaggedServiceIds($locatorTags) as $serviceId => $tags) {
                foreach ($tags as $tag) {
                    if (!isset($tag['alias'])) {
                        throw new \InvalidArgumentException(
                            sprintf('The tagged service "%s" must define have an alias', $serviceId)
                        );
                    }

                    $factories[$tag['alias']] = new ServiceClosureArgument(new Reference($serviceId));
                }
            }
            $this->container->findDefinition($locatorId)->replaceArgument(0, $factories);
        }

        $this->container->setAlias('challenge_validator.dns.resolver', 'challenge_validator.dns.resolver.'.(LibDnsResolver::isSupported() ? 'libdns' : 'simple'));

        // Inject input and output
        $this->container->set('input', $this->input);
        $this->container->set('output', $this->output);
    }

    private function initializeConfiguration()
    {
        $configFile = $this->getApplication()->getConfigFile();
        $referenceFile = $this->getApplication()->getConfigReferenceFile();

        if (!file_exists($configFile)) {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($configFile, file_get_contents($referenceFile));

            $this->notice('Configuration file '.$configFile.' did not exist and has been created.');
        }

        if (!is_readable($configFile)) {
            throw new IOException('Configuration file '.$configFile.' is not readable.');
        }

        $this->configuration = ['acmephp' => Yaml::parse(file_get_contents($configFile))];
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        return $this->getCliLogger()->emergency($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        return $this->getCliLogger()->alert($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        return $this->getCliLogger()->critical($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        return $this->getCliLogger()->error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        return $this->getCliLogger()->warning($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        return $this->getCliLogger()->notice($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        return $this->getCliLogger()->info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        return $this->getCliLogger()->debug($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        return $this->getCliLogger()->log($level, $message, $context);
    }
}
