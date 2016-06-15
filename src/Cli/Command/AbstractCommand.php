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

use AcmePhp\Cli\Application;
use AcmePhp\Cli\Configuration\AcmeConfiguration;
use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractCommand extends Command
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
     * @return RepositoryInterface
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('repository');
    }

    /**
     * @return AcmeClient
     */
    protected function getClient()
    {
        $this->output->writeln('<info>Loading account key pair...</info>');

        $accountKeyPair = $this->getRepository()->loadAccountKeyPair();

        /** @var SecureHttpClient $httpClient */
        $httpClient = $this->getContainer()->get('http.client_factory')->createSecureHttpClient($accountKeyPair);

        /** @var CertificateRequestSigner $csrSigner */
        $csrSigner = $this->getContainer()->get('ssl.csr_signer');

        return new AcmeClient($httpClient, $this->input->getOption('server'), $csrSigner);
    }

    /**
     * @return ContainerBuilder
     */
    protected function getContainer()
    {
        if ($this->container === null) {
            $this->initializeContainer();
        }

        return $this->container;
    }

    /**
     * @return void
     */
    private function initializeContainer()
    {
        if ($this->configuration === null) {
            $this->initializeConfiguration();
        }

        $this->container = new ContainerBuilder();

        // Application services and parameters
        $this->container->set('app', $this->getApplication());
        $this->container->setParameter('app.version', Application::VERSION);
        $this->container->setParameter('app.storage_directory', Application::getStorageDirectory());
        $this->container->setParameter('app.backup_directory', Application::getBackupDirectory());

        // Load configuration
        $processor = new Processor();
        $config = $processor->processConfiguration(new AcmeConfiguration(), $this->configuration);
        $this->container->setParameter('storage.enable_backup', $config['storage']['enable_backup']);
        $this->container->setParameter('storage.post_generate', $config['storage']['post_generate']);
        $this->container->setParameter('monitoring.handlers', $config['monitoring']);

        // Load services
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__.'/../Resources'));
        $loader->load('services.xml');

        $this->container->compile();
    }

    /**
     * @return void
     */
    private function initializeConfiguration()
    {
        $configFile = Application::getConfigFile();
        $referenceFile = Application::getConfigReferenceFile();

        if (!file_exists($configFile)) {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($configFile, file_get_contents($referenceFile));

            $this->output->writeln(
                '<info>Configuration file '.$configFile.' did not exist and has been created.</info>'
            );
        }

        if (!is_readable($configFile)) {
            throw new IOException('Configuration file '.$configFile.' is not readable.');
        }

        $this->configuration = ['acmephp' => Yaml::parse(file_get_contents($configFile))];
    }
}
