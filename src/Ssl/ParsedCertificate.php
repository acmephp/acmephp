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
 * Represent the content of a parsed certificate.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ParsedCertificate
{
    public function __construct(
        private readonly Certificate $source,
        private readonly string $subject,
        private readonly ?string $issuer = null,
        private readonly bool $selfSigned = true,
        private readonly ?\DateTime $validFrom = null,
        private readonly ?\DateTime $validTo = null,
        private readonly ?string $serialNumber = null,
        private readonly array $subjectAlternativeNames = [],
    ) {
        Assert::stringNotEmpty($subject, self::class.'::$subject expected a non empty string. Got: %s');
        Assert::allStringNotEmpty(
            $subjectAlternativeNames,
            self::class.'::$subjectAlternativeNames expected a array of non empty string. Got: %s'
        );
    }

    public function getSource(): Certificate
    {
        return $this->source;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function isSelfSigned(): bool
    {
        return $this->selfSigned;
    }

    public function getValidFrom(): \DateTimeInterface
    {
        return $this->validFrom;
    }

    public function getValidTo(): \DateTimeInterface
    {
        return $this->validTo;
    }

    public function isExpired(): bool
    {
        return $this->validTo < (new \DateTime());
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getSubjectAlternativeNames(): array
    {
        return $this->subjectAlternativeNames;
    }
}
