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

use AcmePhp\Ssl\KeyPair;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface AccountFormatterInterface extends FormatterInterface
{
    /**
     * Create the files list to write for a given account key pair (the global key pair used to
     * interact with the ACME server).
     *
     * @param KeyPair $keyPair
     *
     * @return array An array of files to create associating file names as keys and their contents as values.
     */
    public function createAccountKeyPairFiles(KeyPair $keyPair);
}
