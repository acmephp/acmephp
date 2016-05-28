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
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DefaultLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    public function loadAccountKeyPair(AdapterInterface $adapter)
    {
        return new KeyPair(
            $this->deserializePem($adapter->read('private/_account/public.pem'), PublicKey::class),
            $this->deserializePem($adapter->read('private/_account/private.pem'), PrivateKey::class)
        );
    }

    /**
     * Load the account key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     *
     * @return bool
     */
    public function hasAccountKeyPair(AdapterInterface $adapter)
    {
        return $adapter->has('private/_account/public.pem') && $adapter->has('private/_account/private.pem');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainKeyPair(AdapterInterface $adapter, $domain)
    {
        return new KeyPair(
            $this->deserializePem($adapter->read('private/'.$domain.'/public.pem'), PublicKey::class),
            $this->deserializePem($adapter->read('private/'.$domain.'/private.pem'), PrivateKey::class)
        );
    }

    /**
     * Load a given domain key pair from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return bool
     */
    public function hasDomainKeyPair(AdapterInterface $adapter, $domain)
    {
        return $adapter->has('private/'.$domain.'/public.pem') && $adapter->has('private/'.$domain.'/private.pem');
    }

    /**
     * {@inheritdoc}
     */
    public function loadDomainDistinguishedName(AdapterInterface $adapter, $domain)
    {
        return $this->deserializeJson(
            $adapter->read('certs/'.$domain.'/certificate_request.json'),
            DistinguishedName::class
        );
    }

    /**
     * Load a given domain distinguished name from given adapter.
     *
     * @param AdapterInterface $adapter
     * @param string           $domain
     *
     * @return bool
     */
    public function hasDomainDistinguishedName(AdapterInterface $adapter, $domain)
    {
        return $adapter->has('certs/'.$domain.'/certificate_request.json');
    }
}
