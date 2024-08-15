<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Ssl\Generator\DhKey;

use AcmePhp\Ssl\Generator\KeyOption;
use AcmePhp\Ssl\Generator\OpensslPrivateKeyGeneratorTrait;
use AcmePhp\Ssl\Generator\PrivateKeyGeneratorInterface;
use AcmePhp\Ssl\PrivateKey;
use Webmozart\Assert\Assert;

/**
 * Generate random DH private key using OpenSSL.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class DhKeyGenerator implements PrivateKeyGeneratorInterface
{
    use OpensslPrivateKeyGeneratorTrait;

    public function generatePrivateKey(KeyOption $keyOption): PrivateKey
    {
        Assert::isInstanceOf($keyOption, DhKeyOption::class);

        return $this->generatePrivateKeyFromOpensslOptions(array(
            'private_key_type' => OPENSSL_KEYTYPE_DH,
            'dh' => array(
                'p' => $keyOption->getPrime(),
                'g' => $keyOption->getGenerator(),
            ),
        ));
    }

    public function supportsKeyOption(KeyOption $keyOption): bool
    {
        return $keyOption instanceof DhKeyOption;
    }
}
