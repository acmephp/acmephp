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

use AcmePhp\Ssl\DistinguishedName;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DistinguishedNameHelper extends Helper
{
    public function getName()
    {
        return 'distinguished_name';
    }

    /**
     * @return bool
     */
    public function isReadyForRequest(DistinguishedName $distinguishedName)
    {
        return $distinguishedName->getCountryName()
            && $distinguishedName->getStateOrProvinceName()
            && $distinguishedName->getLocalityName()
            && $distinguishedName->getOrganizationName()
            && $distinguishedName->getOrganizationalUnitName()
            && $distinguishedName->getEmailAddress()
            && $distinguishedName->getCommonName();
    }

    /**
     * @return DistinguishedName
     */
    public function ask(QuestionHelper $helper, InputInterface $input, OutputInterface $output, DistinguishedName $distinguishedName)
    {
        $countryName = $distinguishedName->getCountryName() ?: $helper->ask($input, $output, new Question(
            'What is your country two-letters code (field "C" of the distinguished name, for instance: "US")? : ',
            'FR'
        ));

        $stateOrProvinceName = $distinguishedName->getStateOrProvinceName() ?: $helper->ask($input, $output, new Question(
            'What is your country province (field "ST" of the distinguished name, for instance: "California")? : '
        ));

        $localityName = $distinguishedName->getLocalityName() ?: $helper->ask($input, $output, new Question(
            'What is your locality (field "L" of the distinguished name, for instance: "Mountain View")? : '
        ));

        $organizationName = $distinguishedName->getOrganizationName() ?: $helper->ask($input, $output, new Question(
            'What is your organization/company (field "O" of the distinguished name, for instance: "Acme PHP")? : '
        ));

        $organizationalUnitName = $distinguishedName->getOrganizationalUnitName() ?: $helper->ask($input, $output, new Question(
            'What is your unit/department in your organization (field "OU" of the distinguished name, for instance: "Sales")? : '
        ));

        $emailAddress = $distinguishedName->getEmailAddress() ?: $helper->ask($input, $output, new Question(
            'What is your e-mail address (field "E" of the distinguished name)? : '
        ));

        return new DistinguishedName(
            $distinguishedName->getCommonName(),
            $countryName,
            $stateOrProvinceName,
            $localityName,
            $organizationName,
            $organizationalUnitName,
            $emailAddress,
            $distinguishedName->getSubjectAlternativeNames()
        );
    }
}
