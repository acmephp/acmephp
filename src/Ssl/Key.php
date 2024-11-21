<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

use Webmozart\Assert\Assert;

/**
 * Represent a SSL key.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
abstract class Key
{
    /**
     * This is a very lenient parser.
     * It will detect the BEGIN and END labels and accept any data as long as it contains base64 chars combined with
     * whitespace. After a padding character only whitespace is allowed.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc7468#section-5.1
     */
    private const string REGEX = '~^.*-----BEGIN (?P<label>.*?)-----(?P<data>[[:alnum:]/+\s]*[\s=]*)-----END (?P=label)-----$~msn';

    /** @var string */
    protected $keyPEM;

    private readonly string $der;

    public function __construct(string $keyPEM)
    {
        // Parse the PEM into a DER to detect errors earlier.
        Assert::regex($keyPEM, self::REGEX, 'The PEM file is not formatted correctly. Got %s');
        $this->keyPEM = $keyPEM;
        $this->der = $this->extractDER($keyPEM);
    }

    private function extractDER(string $pem): string
    {
        preg_match(self::REGEX, $pem, $matches);
        $result = base64_decode($matches['data'], true);
        if (false === $result) {
            throw new \RuntimeException('Failed to decode PEM');
        }

        return $result;
    }

    public function getPEM(): string
    {
        return $this->keyPEM;
    }

    final public function getDER(): string
    {
        return $this->der;
    }

    /**
     * @return resource|\OpenSSLAsymmetricKey
     */
    abstract public function getResource();
}
