<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Persistence\Formatter;

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface FormatterInterface
{
    /**
     * Create the files list to write for a given domain key pair.
     *
     * @param string  $domain
     * @param KeyPair $keyPair
     *
     * @return array An array of files to create associating file names as keys and their contents as values.
     */
    public function createDomainKeyPairFiles($domain, KeyPair $keyPair);

    /**
     * Create the files list to write for a given distinguished name associated to a given domain.
     *
     * @param string            $domain
     * @param DistinguishedName $distinguishedName
     *
     * @return array An array of files to create associating file names as keys and their contents as values.
     */
    public function createDomainDistinguishedNameFiles($domain, DistinguishedName $distinguishedName);

    /**
     * Create the files list to write for a given certificate associated to a given domain.
     *
     * @param string      $domain
     * @param KeyPair     $keyPair     The domain key pair
     * @param Certificate $certificate The domain certificate
     *
     * @return array An array of files to create associating file names as keys and their contents as values.
     */
    public function createDomainCertificateFiles($domain, KeyPair $keyPair, Certificate $certificate);
}
