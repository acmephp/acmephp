<?php

/*
 * This file is part of the ACME PHP library.
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
    const NEW_REGISTRATION = 'new-reg';
    const RECOVER_REGISTRATION = 'recover-reg';
    const NEW_AUTHORIZATION = 'new-authz';
    const NEW_CERTIFICATE = 'new-cert';
    const REVOKE_CERTIFICATE = 'revoke-cert';
    const REGISTRATION = 'reg';
    const AUTHORIZATION = 'authz';
    const CHALLENGE = 'challenge';
    const CERTIFICATE = 'cert';

    /**
     * @var array
     */
    private $serverResources;

    /**
     * @param array $serverResources
     */
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
            self::NEW_REGISTRATION,
            self::RECOVER_REGISTRATION,
            self::NEW_AUTHORIZATION,
            self::NEW_CERTIFICATE,
            self::REVOKE_CERTIFICATE,
            self::REGISTRATION,
            self::AUTHORIZATION,
            self::CHALLENGE,
            self::CERTIFICATE,
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
