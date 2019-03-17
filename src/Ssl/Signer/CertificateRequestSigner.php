<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Signer;

use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\Exception\CSRSigningException;

/**
 * Provide tools to sign certificate request.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CertificateRequestSigner
{
    /**
     * Generate a CSR from the given distinguishedName and keyPair.
     *
     * @param CertificateRequest $certificateRequest
     *
     * @return string
     */
    public function signCertificateRequest(CertificateRequest $certificateRequest)
    {
        $csrObject = $this->createCsrWithSANsObject($certificateRequest);

        if (!$csrObject || !openssl_csr_export($csrObject, $csrExport)) {
            throw new CSRSigningException(sprintf('OpenSSL CSR signing failed with error: %s', openssl_error_string()));
        }

        return $csrExport;
    }

    /**
     * Generate a CSR object with SANs from the given distinguishedName and keyPair.
     *
     * @param CertificateRequest $certificateRequest
     *
     * @return mixed
     */
    protected function createCsrWithSANsObject(CertificateRequest $certificateRequest)
    {
        $sslConfigTemplate = <<<'EOL'
[ req ]
distinguished_name = req_distinguished_name
req_extensions = v3_req
[ req_distinguished_name ]
[ v3_req ]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @req_subject_alt_name
[ req_subject_alt_name ]
%s
EOL;
        $sslConfigDomains = [];

        $distinguishedName = $certificateRequest->getDistinguishedName();
        $domains = array_merge(
            [$distinguishedName->getCommonName()],
            $distinguishedName->getSubjectAlternativeNames()
        );

        foreach (array_values($domains) as $index => $domain) {
            $sslConfigDomains[] = 'DNS.'.($index + 1).' = '.$domain;
        }

        $sslConfigContent = sprintf($sslConfigTemplate, implode("\n", $sslConfigDomains));
        $sslConfigFile = tempnam(sys_get_temp_dir(), 'acmephp_');

        try {
            file_put_contents($sslConfigFile, $sslConfigContent);

            $resource = $certificateRequest->getKeyPair()->getPrivateKey()->getResource();

            $csr = openssl_csr_new(
                $this->getCSRPayload($distinguishedName),
                $resource,
                [
                    'digest_alg' => 'sha256',
                    'config' => $sslConfigFile,
                ]
            );

            openssl_free_key($resource);

            if (!$csr) {
                throw new CSRSigningException(
                    sprintf('OpenSSL CSR signing failed with error: %s', openssl_error_string())
                );
            }

            return $csr;
        } finally {
            unlink($sslConfigFile);
        }
    }

    /**
     * Retrieves a CSR payload from the given distinguished name.
     *
     * @param DistinguishedName $distinguishedName
     *
     * @return array
     */
    private function getCSRPayload(DistinguishedName $distinguishedName)
    {
        $payload = [];
        if (null !== $countryName = $distinguishedName->getCountryName()) {
            $payload['countryName'] = $countryName;
        }
        if (null !== $stateOrProvinceName = $distinguishedName->getStateOrProvinceName()) {
            $payload['stateOrProvinceName'] = $stateOrProvinceName;
        }
        if (null !== $localityName = $distinguishedName->getLocalityName()) {
            $payload['localityName'] = $localityName;
        }
        if (null !== $OrganizationName = $distinguishedName->getOrganizationName()) {
            $payload['organizationName'] = $OrganizationName;
        }
        if (null !== $organizationUnitName = $distinguishedName->getOrganizationalUnitName()) {
            $payload['organizationalUnitName'] = $organizationUnitName;
        }
        if (null !== $commonName = $distinguishedName->getCommonName()) {
            $payload['commonName'] = $commonName;
        }
        if (null !== $emailAddress = $distinguishedName->getEmailAddress()) {
            $payload['emailAddress'] = $emailAddress;
        }

        return $payload;
    }
}
