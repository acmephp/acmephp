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

use AcmePhp\Cli\Exception\CommandFlowException;
use AcmePhp\Cli\Repository\RepositoryInterface;
use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\Challenge\Dns\LibDnsResolver;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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
     * @var ContainerBuilder|null
     */
    private $container;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function getRepository(): RepositoryInterface
    {
        $this->debug('Loading repository');

        return $this->getContainer()->get('repository');
    }

    protected function getClient(string $directoryUrl): AcmeClient
    {
        $this->debug('Creating Acme client');
        $this->notice('Loading account key pair...');

        if (!$this->getRepository()->hasAccountKeyPair()) {
            throw new CommandFlowException('register in ACME servers', 'register');
        }
        $accountKeyPair = $this->getRepository()->loadAccountKeyPair();

        /** @var SecureHttpClient $httpClient */
        $httpClient = $this->getContainer()->get('http.client_factory')->createSecureHttpClient($accountKeyPair);

        /** @var CertificateRequestSigner $csrSigner */
        $csrSigner = $this->getContainer()->get('ssl.csr_signer');

        return new AcmeClient($httpClient, $directoryUrl, $csrSigner);
    }

    protected function getCliLogger(): LoggerInterface
    {
        return $this->getContainer()->get('cli_logger');
    }

    protected function getContainer(): ContainerBuilder
    {
        if (null === $this->container) {
            $this->initializeContainer();
        }

        return $this->container;
    }

    private function initializeContainer()
    {
        $this->container = new ContainerBuilder();

        // Application services and parameters
        $this->container->set('app', $this->getApplication());
        $this->container->set('container', $this->container);
        $this->container->setParameter('app.storage_directory', $this->getApplication()->getStorageDirectory());

        // Load services
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__.'/../Resources'));
        $loader->load('services.xml');

        foreach ($this->container->findTaggedServiceIds('acmephp.service_locator') as $locatorId => $locatorTags) {
            if (!isset($locatorTags[0]['tag'])) {
                throw new \InvalidArgumentException(sprintf('The tagged service "%s" must define have an alias', $locatorId));
            }
            $locatorTags = $locatorTags[0]['tag'];
            $factories = [];
            foreach ($this->container->findTaggedServiceIds($locatorTags) as $serviceId => $tags) {
                foreach ($tags as $tag) {
                    if (!isset($tag['alias'])) {
                        throw new \InvalidArgumentException(sprintf('The tagged service "%s" must define have an alias', $serviceId));
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

    public function emergency($message, array $context = [])
    {
        $this->getCliLogger()->emergency($message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->getCliLogger()->alert($message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->getCliLogger()->critical($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->getCliLogger()->error($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->getCliLogger()->warning($message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->getCliLogger()->notice($message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->getCliLogger()->info($message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->getCliLogger()->debug($message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $this->getCliLogger()->log($level, $message, $context);
    }
}
