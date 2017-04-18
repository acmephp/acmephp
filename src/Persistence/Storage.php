<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence;

use AcmePhp\Persistence\Adapter\AdapterInterface;
use AcmePhp\Persistence\Formatter\AccountFormatterInterface;
use AcmePhp\Persistence\Formatter\DefaultFormatter;
use AcmePhp\Persistence\Formatter\FormatterInterface;
use AcmePhp\Persistence\Loader\DefaultLoader;
use AcmePhp\Persistence\Loader\LoaderInterface;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Storage implements StorageInterface
{
    /**
     * @var AdapterInterface[]
     */
    private $adapters;

    /**
     * @var FormatterInterface[]
     */
    private $formatters;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var bool
     */
    private $backupEnabled;

    /**
     * @param AdapterInterface $mainAdapter
     * @param bool             $backupEnabled
     * @param LoaderInterface  $loader
     */
    public function __construct(AdapterInterface $mainAdapter, $backupEnabled = true, LoaderInterface $loader = null)
    {
        $this->adapters = [$mainAdapter];
        $this->formatters = [new DefaultFormatter()];
        $this->loader = $loader ?: new DefaultLoader();
        $this->backupEnabled = $backupEnabled;
    }

    /**
     * @param AdapterInterface $adapter
     */
    public function addAdapter(AdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;
    }

    /**
     * @param FormatterInterface $formatter
     */
    public function addFormatter(FormatterInterface $formatter)
    {
        $this->formatters[] = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function storeCertificateResponse(CertificateResponse $certificateResponse)
    {
        $distinguishedName = $certificateResponse->getCertificateRequest()->getDistinguishedName();
        $domain = $distinguishedName->getCommonName();

        $this->storeDomainKeyPair($domain, $certificateResponse->getCertificateRequest()->getKeyPair());
        $this->storeDomainDistinguishedName($domain, $distinguishedName);
        $this->storeDomainCertificate($domain, $certificateResponse->getCertificate());
    }

    /**
     * {@inheritdoc}
     */
    public function storeAccountKeyPair(KeyPair $keyPair)
    {
        foreach ($this->adapters as $adapter) {
            foreach ($this->formatters as $formatter) {
                if ($formatter instanceof AccountFormatterInterface) {
                    $this->createFiles($adapter, $formatter->createAccountKeyPairFiles($keyPair));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainKeyPair($domain, KeyPair $keyPair)
    {
        foreach ($this->adapters as $adapter) {
            foreach ($this->formatters as $formatter) {
                $this->createFiles($adapter, $formatter->createDomainKeyPairFiles($domain, $keyPair));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainDistinguishedName($domain, DistinguishedName $dn)
    {
        foreach ($this->adapters as $adapter) {
            foreach ($this->formatters as $formatter) {
                $this->createFiles($adapter, $formatter->createDomainDistinguishedNameFiles($domain, $dn));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeDomainCertificate($domain, Certificate $certificate)
    {
        $keyPair = $this->loadDomainKeyPair($domain);

        foreach ($this->adapters as $adapter) {
            foreach ($this->formatters as $formatter) {
                $this->createFiles($adapter, $formatter->createDomainCertificateFiles($domain, $keyPair, $certificate));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadAccountKeyPair()
    {
        return $this->loader->loadAccountKeyPair($this->adapters[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainKeyPair($domain)
    {
        return $this->loader->loadDomainKeyPair($this->adapters[0], $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainDistinguishedName($domain)
    {
        return $this->loader->loadDomainDistinguishedName($this->adapters[0], $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccountKeyPair()
    {
        return $this->loader->hasAccountKeyPair($this->adapters[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainKeyPair($domain)
    {
        return $this->loader->hasDomainKeyPair($this->adapters[0], $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDomainDistinguishedName($domain)
    {
        return $this->loader->hasDomainDistinguishedName($this->adapters[0], $domain);
    }

    /**
     * @param AdapterInterface $adapter
     * @param array            $files
     */
    private function createFiles(AdapterInterface $adapter, array $files)
    {
        foreach ($files as $filename => $content) {
            $dirname = dirname($filename);

            if (!$adapter->has($dirname)) {
                $adapter->mkdir($dirname);
            }

            if ($this->backupEnabled && $adapter->has($filename)) {
                $adapter->write($filename.'.back', $adapter->read($filename));
            }

            $adapter->write($filename, $content);
        }
    }
}
