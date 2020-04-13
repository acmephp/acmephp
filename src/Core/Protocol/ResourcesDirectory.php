<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use Webmozart\Assert\Assert;

/**
 * Represent a ACME resources directory.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ResourcesDirectory
{
    const NEW_ACCOUNT = 'newAccount';
    const NEW_ORDER = 'newOrder';
    const NEW_NONCE = 'newNonce';
    const REVOKE_CERT = 'revokeCert';

    /**
     * @var array
     */
    private $serverResources;

    public function __construct(array $serverResources)
    {
        $this->serverResources = $serverResources;
    }

    /**
     * @return string[]
     */
    public static function getResourcesNames()
    {
        return [
            self::NEW_ACCOUNT,
            self::NEW_ORDER,
            self::NEW_NONCE,
            self::REVOKE_CERT,
        ];
    }

    /**
     * Find a resource URL.
     *
     * @param string $resource
     *
     * @return string
     */
    public function getResourceUrl($resource)
    {
        Assert::oneOf(
            $resource,
            self::getResourcesNames(),
            'Resource type "%s" is not supported by the ACME server (supported: %2$s)'
        );

        return isset($this->serverResources[$resource]) ? $this->serverResources[$resource] : null;
    }
}
