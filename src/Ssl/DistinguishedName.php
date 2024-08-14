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
 * Represent a Distinguished Name.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DistinguishedName
{
    private readonly array $subjectAlternativeNames;

    public function __construct(
        private readonly string $commonName,
        private readonly ?string $countryName = null,
        private readonly ?string $stateOrProvinceName = null,
        private readonly ?string $localityName = null,
        private readonly ?string $organizationName = null,
        private readonly ?string $organizationalUnitName = null,
        private readonly ?string $emailAddress = null,
        array $subjectAlternativeNames = []
    ) {
        Assert::stringNotEmpty($commonName, self::class.'::$commonName expected a non empty string. Got: %s');
        Assert::allStringNotEmpty(
            $subjectAlternativeNames,
            self::class.'::$subjectAlternativeNames expected an array of non empty string. Got: %s'
        );

        $this->subjectAlternativeNames = array_diff(array_unique($subjectAlternativeNames), [$commonName]);
    }

    public function getCommonName(): string
    {
        return $this->commonName;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getStateOrProvinceName(): ?string
    {
        return $this->stateOrProvinceName;
    }

    public function getLocalityName(): ?string
    {
        return $this->localityName;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function getOrganizationalUnitName(): ?string
    {
        return $this->organizationalUnitName;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getSubjectAlternativeNames(): array
    {
        return $this->subjectAlternativeNames;
    }
}
