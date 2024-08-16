<?php

declare(strict_types=1);

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

    public function __construct(?ContainerInterface $filesystemFactoryLocator = null, ?HttpDataExtractor $extractor = null)
    {
        $this->filesystemFactoryLocator = $filesystemFactoryLocator ?: new ServiceLocator([]);
        $this->extractor = $extractor ?: new HttpDataExtractor();
        $this->filesystem = new NullAdapter();
    }

    public function configure(array $config)
    {
        Assert::keyExists($config, 'adapter', 'configure::$config expected an array with the key %s.');

        /** @var FilesystemFactoryInterface $factory */
        $factory = $this->filesystemFactoryLocator->get($config['adapter']);
        $this->filesystem = $factory->create($config);
    }

    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'http-01' === $authorizationChallenge->getType();
    }

    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        $checkPath = $this->extractor->getCheckPath($authorizationChallenge);
        $checkContent = $this->extractor->getCheckContent($authorizationChallenge);

        $this->filesystem->write($checkPath, $checkContent);
    }

    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $checkPath = $this->extractor->getCheckPath($authorizationChallenge);

        $this->filesystem->delete($checkPath);
    }
}
