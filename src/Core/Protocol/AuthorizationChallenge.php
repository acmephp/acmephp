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
 * Represent a ACME challenge.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AuthorizationChallenge
{
    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $payload;

    /**
     * @param string $domain
     * @param string $type
     * @param string $url
     * @param string $token
     * @param string $payload
     */
    public function __construct($domain, $type, $url, $token, $payload)
    {
        Assert::stringNotEmpty($domain, 'Challenge::$domain expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($type, 'Challenge::$type expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($url, 'Challenge::$url expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($token, 'Challenge::$token expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($payload, 'Challenge::$payload expected a non-empty string. Got: %s');

        $this->domain = $domain;
        $this->type = $type;
        $this->url = $url;
        $this->token = $token;
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain'   => $this->getDomain(),
            'type'     => $this->getType(),
            'url'      => $this->getUrl(),
            'token'    => $this->getToken(),
            'payload'  => $this->getPayload(),
        ];
    }

    /**
     * @param array $data
     *
     * @return AuthorizationChallenge
     */
    public static function fromArray(array $data)
    {
        return new self(
            $data['domain'],
            $data['type'],
            $data['url'],
            $data['token'],
            $data['payload']
        );
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
