<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\ChallengeSolver\Dns;

use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;

/**
 * Validator for DNS challenges.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DnsValidator
{
    /**
     * Internally validate the challenge by performing the same kind of test than the CA.
     *
     * @param string $recordName
     * @param string $recordValue
     * @param int    $timeout
     */
    public function validate($recordName, $recordValue, $timeout = 60)
    {
        $limitEndTime = microtime(true) + $timeout;

        do {
            if ($this->isValid($recordName, $recordValue)) {
                return;
            }

            sleep(1);
        } while ($limitEndTime > microtime(true));

        throw new ChallengeTimedOutException('Unable to validate timeout in the given time');
    }

    /**
     * Returns whether or not the url return the exepected content.
     *
     * @param string $recordName
     * @param string $recordValue
     *
     * @return bool
     */
    public function isValid($recordName, $recordValue)
    {
        foreach (dns_get_record($recordName, DNS_TXT) as $record) {
            if (in_array($recordValue, $record['entries'])) {
                return true;
            }
        }

        return false;
    }
}
