<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\ConfigurableServiceInterface;
use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Filesystem\Adapter\NullAdapter;
use AcmePhp\Core\Filesystem\FilesystemFactoryInterface;
use AcmePhp\Core\Filesystem\FilesystemInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Webmozart\Assert\Assert;

/**
 * ACME HTTP solver through ftp upload.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class FilesystemSolver implements SolverInterface, ConfigurableServiceInterface
{
    /**
     * @var ContainerInterface
     */
    private $filesystemFactoryLocator;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var HttpDataExtractor
     */
    private $extractor;

    /**
     * @param ContainerInterface $filesystemFactoryLocator
     * @param HttpDataExtractor  $extractor
     */
    public function __construct(ContainerInterface $filesystemFactoryLocator = null, HttpDataExtractor $extractor = null)
    {
        $this->filesystemFactoryLocator = $filesystemFactoryLocator = null ? new ServiceLocator([]) : $filesystemFactoryLocator;
        $this->extractor = null === $extractor ? new HttpDataExtractor() : $extractor;
        $this->filesystem = new NullAdapter();
    }

    public function configure(array $config)
    {
        Assert::keyExists($config, 'adapter', 'configure::$config expected an array with the key %s.');

        /** @var FilesystemFactoryInterface $factory */
        $factory = $this->filesystemFactoryLocator->get($config['adapter']);
        $this->filesystem = $factory->create($config);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge)
    {
        return 'http-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        $checkPath = $this->extractor->getCheckPath($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        $this->filesystem->write($checkPath, $checkContent);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $checkPath = $this->extractor->getCheckPath($authorizationChallenge);
        //$this->filesystem->delete($checkPath);
    }
}
