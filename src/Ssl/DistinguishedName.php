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
    /** @var string */
    private $commonName;

    /** @var string */
    private $countryName;

    /** @var string */
    private $stateOrProvinceName;

    /** @var string */
    private $localityName;

    /** @var string */
    private $organizationName;

    /** @var string */
    private $organizationalUnitName;

    /** @var string */
    private $emailAddress;

    /** @var array */
    private $subjectAlternativeNames;

    public function __construct(
        string $commonName,
        ?string $countryName = null,
        ?string $stateOrProvinceName = null,
        ?string $localityName = null,
        ?string $organizationName = null,
        ?string $organizationalUnitName = null,
        ?string $emailAddress = null,
        array $subjectAlternativeNames = [],
    ) {
        Assert::stringNotEmpty($commonName, __CLASS__ . '::$commonName expected a non empty string. Got: %s');
        Assert::allStringNotEmpty(
            $subjectAlternativeNames,
            __CLASS__ . '::$subjectAlternativeNames expected an array of non empty string. Got: %s',
        );

        $this->commonName = $commonName;
        $this->countryName = $countryName;
        $this->stateOrProvinceName = $stateOrProvinceName;
        $this->localityName = $localityName;
        $this->organizationName = $organizationName;
        $this->organizationalUnitName = $organizationalUnitName;
        $this->emailAddress = $emailAddress;
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
