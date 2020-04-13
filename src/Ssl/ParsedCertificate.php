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
        $subject,
        $issuer = null,
        $selfSigned = true,
        \DateTime $validFrom = null,
        \DateTime $validTo = null,
        $serialNumber = null,
        array $subjectAlternativeNames = []
    ) {
        Assert::stringNotEmpty($subject, __CLASS__.'::$subject expected a non empty string. Got: %s');
        Assert::nullOrString($issuer, __CLASS__.'::$issuer expected a string or null. Got: %s');
        Assert::nullOrBoolean($selfSigned, __CLASS__.'::$selfSigned expected a boolean or null. Got: %s');
        Assert::nullOrString($serialNumber, __CLASS__.'::$serialNumber expected a string or null. Got: %s');
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

    /**
     * @return Certificate
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @return bool
     */
    public function isSelfSigned()
    {
        return $this->selfSigned;
    }

    /**
     * @return \DateTime
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * @return \DateTime
     */
    public function getValidTo()
    {
        return $this->validTo;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->validTo < (new \DateTime());
    }

    /**
     * @return string
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * @return array
     */
    public function getSubjectAlternativeNames()
    {
        return $this->subjectAlternativeNames;
    }
}
