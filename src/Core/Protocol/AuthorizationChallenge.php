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
     * @var string
     */
    private $location;

    /**
     * @param string $domain
     * @param string $url
     * @param string $token
     * @param string $payload
     * @param $location
     */
    public function __construct($domain, $url, $token, $payload, $location)
    {
        Assert::stringNotEmpty($domain, 'Challenge::$domain expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($url, 'Challenge::$url expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($token, 'Challenge::$token expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($payload, 'Challenge::$payload expected a non-empty string. Got: %s');

        $this->domain = $domain;
        $this->url = $url;
        $this->token = $token;
        $this->payload = $payload;
        $this->location = $location;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain'   => $this->getDomain(),
            'url'      => $this->getUrl(),
            'token'    => $this->getToken(),
            'payload'  => $this->getPayload(),
            'location' => $this->getLocation(),
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
            $data['url'],
            $data['token'],
            $data['payload'],
            $data['location']
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

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }
}
