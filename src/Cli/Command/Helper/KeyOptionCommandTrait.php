<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command\Helper;

use AcmePhp\Ssl\Generator\EcKey\EcKeyOption;
use AcmePhp\Ssl\Generator\RsaKey\RsaKeyOption;
use AcmePhp\Ssl\DistinguishedName;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
trait KeyOptionCommandTrait
{
    private function createKeyOption($keyType)
    {
        switch (strtoupper($keyType)) {
            case 'RSA':
                return new RsaKeyOption();
            case 'EC':
                return new EcKeyOption();
            default:
                throw new \InvalidArgumentException(sprintf('The keyType "%s" is not valid. Supported types are: RSA, EC', strtoupper($keyType)));
        }
    }


    /**
     * Retrieve the stored distinguishedName or create a new one if needed.
     *
     * @param string $domain
     * @param array  $alternativeNames
     *
     * @return DistinguishedName
     */
    private function getOrCreateDistinguishedName($domain, array $alternativeNames)
    {
        if ($this->repository->hasDomainDistinguishedName($domain)) {
            $original = $this->repository->loadDomainDistinguishedName($domain);

            $distinguishedName = new DistinguishedName(
                $domain,
                $this->input->getOption('country') ?: $original->getCountryName(),
                $this->input->getOption('province') ?: $original->getStateOrProvinceName(),
                $this->input->getOption('locality') ?: $original->getLocalityName(),
                $this->input->getOption('organization') ?: $original->getOrganizationName(),
                $this->input->getOption('unit') ?: $original->getOrganizationalUnitName(),
                $this->input->getOption('email') ?: $original->getEmailAddress(),
                $alternativeNames
            );
        } else {
            // Ask DistinguishedName
            $distinguishedName = new DistinguishedName(
                $domain,
                $this->input->getOption('country'),
                $this->input->getOption('province'),
                $this->input->getOption('locality'),
                $this->input->getOption('organization'),
                $this->input->getOption('unit'),
                $this->input->getOption('email'),
                $alternativeNames
            );

            /** @var DistinguishedNameHelper $helper */
            $helper = $this->getHelper('distinguished_name');

            if (!$helper->isReadyForRequest($distinguishedName)) {
                $this->info("\n\nSome informations about you or your company are required for the certificate:\n");

                $distinguishedName = $helper->ask(
                    $this->getHelper('question'),
                    $this->input,
                    $this->output,
                    $distinguishedName
                );
            }
        }

        $this->repository->storeDomainDistinguishedName($domain, $distinguishedName);

        return $distinguishedName;
    }
}
