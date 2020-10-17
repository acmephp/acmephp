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
    /** @var Certificate */
    private $source;

    /** @var string */
    private $subject;

    /** @var string */
    private $issuer;

    /** @var bool */
    private $selfSigned;

    /** @var \DateTime */
    private $validFrom;

    /** @var \DateTime */
    private $validTo;

    /** @var string */
    private $serialNumber;

    /** @var array */
    private $subjectAlternativeNames;

    /**
     * @param string    $subject
     * @param string    $issuer
     * @param bool      $selfSigned
     * @param \DateTime $validFrom
     * @param \DateTime $validTo
     * @param string    $serialNumber
     */
    public function __construct(
        Certificate $source,
        string $subject,
        string $issuer = null,
        bool $selfSigned = true,
        \DateTime $validFrom = null,
        \DateTime $validTo = null,
        string $serialNumber = null,
        array $subjectAlternativeNames = []
    ) {
        Assert::stringNotEmpty($subject, __CLASS__.'::$subject expected a non empty string. Got: %s');
        Assert::allStringNotEmpty(
            $subjectAlternativeNames,
            __CLASS__.'::$subjectAlternativeNames expected a array of non empty string. Got: %s'
        );

        $this->source = $source;
        $this->subject = $subject;
        $this->issuer = $issuer;
        $this->selfSigned = $selfSigned;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->serialNumber = $serialNumber;
        $this->subjectAlternativeNames = $subjectAlternativeNames;
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
