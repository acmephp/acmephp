<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Parser;

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\Exception\CertificateParsingException;
use AcmePhp\Ssl\ParsedCertificate;

/**
 * Parse certificate to extract metadata.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CertificateParser
{
    /**
     * Parse the certificate.
     *
     * @return ParsedCertificate
     */
    public function parse(Certificate $certificate)
    {
        $rawData = openssl_x509_parse($certificate->getPEM());

        if (!\is_array($rawData)) {
            throw new CertificateParsingException(sprintf('Fail to parse certificate with error: %s', openssl_error_string()));
        }

        if (!isset($rawData['subject']['CN'])) {
            throw new CertificateParsingException('Missing expected key "subject.cn" in certificate');
        }
        if (!isset($rawData['serialNumber'])) {
            throw new CertificateParsingException('Missing expected key "serialNumber" in certificate');
        }
        if (!isset($rawData['validFrom_time_t'])) {
            throw new CertificateParsingException('Missing expected key "validFrom_time_t" in certificate');
        }
        if (!isset($rawData['validTo_time_t'])) {
            throw new CertificateParsingException('Missing expected key "validTo_time_t" in certificate');
        }

        $subjectAlternativeName = [];

        if (isset($rawData['extensions']['subjectAltName'])) {
            $subjectAlternativeName = array_map(
                function ($item) {
                    return explode(':', trim($item), 2)[1];
                },
                array_filter(
                    explode(
                        ',',
                        $rawData['extensions']['subjectAltName']
                    ),
                    function ($item) {
                        return false !== strpos($item, ':');
                    }
                )
            );
        }

        return new ParsedCertificate(
            $certificate,
            $rawData['subject']['CN'],
            isset($rawData['issuer']['CN']) ? $rawData['issuer']['CN'] : null,
            $rawData['subject'] === $rawData['issuer'],
            new \DateTime('@'.$rawData['validFrom_time_t']),
            new \DateTime('@'.$rawData['validTo_time_t']),
            $rawData['serialNumber'],
            $subjectAlternativeName
        );
    }
}
