<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl;

use Webmozart\Assert\Assert;

/**
 * Represent a Certificate.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Certificate
{
    /** @var string */
    private $certificatePEM;

    /** @var Certificate */
    private $issuerCertificate;

    /**
     * @param string      $certificatePEM
     * @param Certificate $issuerCertificate
     */
    public function __construct($certificatePEM, Certificate $issuerCertificate = null)
    {
        Assert::stringNotEmpty($certificatePEM, __CLASS__.'::$certificatePEM should not be an empty string. Got %s');

        $this->certificatePEM = $certificatePEM;
        $this->issuerCertificate = $issuerCertificate;
    }

    /**
     * @return Certificate[]
     */
    public function getIssuerChain()
    {
        $chain = [];
        $issuerCertificate = $this->getIssuerCertificate();

        while (null !== $issuerCertificate) {
            $chain[] = $issuerCertificate;
            $issuerCertificate = $issuerCertificate->getIssuerCertificate();
        }

        return $chain;
    }

    /**
     * @return string
     */
    public function getPEM()
    {
        return $this->certificatePEM;
    }

    /**
     * @return Certificate
     */
    public function getIssuerCertificate()
    {
        return $this->issuerCertificate;
    }
}
