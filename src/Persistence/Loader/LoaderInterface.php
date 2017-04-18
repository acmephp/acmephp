<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Loader;

use AcmePhp\Persistence\Adapter\AdapterInterface;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * Interface for all the loaders.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface LoaderInterface
{
    /**
     * Load the account key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     *
     * @return bool
     */
    public function hasAccountKeyPair(AdapterInterface $adapter);

    /**
     * Load the account key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     *
     * @return KeyPair
     */
    public function loadAccountKeyPair(AdapterInterface $adapter);

    /**
     * Load a given domain key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return bool
     */
    public function hasDomainKeyPair(AdapterInterface $adapter, $domain);

    /**
     * Load a given domain key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return KeyPair
     */
    public function loadDomainKeyPair(AdapterInterface $adapter, $domain);

    /**
     * Load a given domain distinguished name from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return bool
     */
    public function hasDomainDistinguishedName(AdapterInterface $adapter, $domain);

    /**
     * Load a given domain distinguished name from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return DistinguishedName
     */
    public function loadDomainDistinguishedName(AdapterInterface $adapter, $domain);
}
