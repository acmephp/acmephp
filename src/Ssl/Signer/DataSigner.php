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

use AcmePhp\Ssl\Exception\DataSigningException;
use AcmePhp\Ssl\PrivateKey;
use Webmozart\Assert\Assert;
use AcmePhp\Ssl\PublicKey;
use AcmePhp\Ssl\Exception\DataCheckingSignException;
use AcmePhp\Core\Exception\AcmeCoreClientException;

/**
 * Provide tools to sign data using a private key.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DataSigner
{
    const FORMAT_DER = 'DER';
    const FORMAT_ECDSA = 'ECDSA';

    /**
     * Generate a signature of the given data using a private key and an algorithm.
     *
     * @param string     $data       Data to sign
     * @param PrivateKey $privateKey Key used to sign
     * @param int        $algorithm  Signature algorithm defined by constants OPENSSL_ALGO_*
     * @param string     $format     Format of the output
     *
     * @return string
     */
    public function signData($data, PrivateKey $privateKey, $algorithm = OPENSSL_ALGO_SHA256, $format = self::FORMAT_DER)
    {
        Assert::oneOf($format, [self::FORMAT_ECDSA, self::FORMAT_DER], 'The format %s to sign request does not exists. Available format: %s');

        $resource = $privateKey->getResource();
        if (!openssl_sign($data, $signature, $resource, $algorithm)) {
            throw new DataSigningException(
                sprintf('OpenSSL data signing failed with error: %s', openssl_error_string())
            );
        }

        openssl_free_key($resource);

        switch ($format) {
            case self::FORMAT_DER:
                return $signature;
            case self::FORMAT_ECDSA:
                switch ($algorithm) {
                    case OPENSSL_ALGO_SHA256:
                        return $this->DERtoECDSA($signature, 64);
                    case OPENSSL_ALGO_SHA384:
                        return $this->DERtoECDSA($signature, 96);
                    case OPENSSL_ALGO_SHA512:
                        return $this->DERtoECDSA($signature, 132);
                }
                throw new DataSigningException('Unable to generate a ECDSA signature with the given algorithm');
            default:
                throw new DataSigningException('The given format does exists');
        }
    }

    /**
     * Check sign
     *
     * @param string $signature
     * @param string $data
     * @param PublicKey $publicKey
     * @param int $algorithm
     * @param string $format
     * @return void
     */
    public function checkSign($signature, $data, PublicKey $publicKey, $algorithm = OPENSSL_ALGO_SHA256, $format = self::FORMAT_DER)
    {
        Assert::oneOf($format, [self::FORMAT_ECDSA, self::FORMAT_DER], 'The format %s to sign request does not exists. Available format: %s');

        $resource = $publicKey->getResource();
        if (1 != openssl_verify($data, $signature, $resource, $algorithm)) {
            throw new DataCheckingSignException(
                sprintf('OpenSSL data checking sign failed with error: %s', openssl_error_string())
            );
        }

        openssl_free_key($resource);
    }

    /**
     * Convert a DER signature into ECDSA.
     *
     * The code is a copy/paste from another lib (web-token/jwt-core) which is not compatible with php <= 7.0
     *
     * @see https://github.com/web-token/jwt-core/blob/master/Util/ECSignature.php
     */
    private function DERtoECDSA($der, $partLength)
    {
        $hex = unpack('H*', $der)[1];
        if ('30' !== mb_substr($hex, 0, 2, '8bit')) { // SEQUENCE
            throw new DataSigningException('Invalid signature provided');
        }
        if ('81' === mb_substr($hex, 2, 2, '8bit')) { // LENGTH > 128
            $hex = mb_substr($hex, 6, null, '8bit');
        } else {
            $hex = mb_substr($hex, 4, null, '8bit');
        }
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new DataSigningException('Invalid signature provided');
        }

        $Rl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $R = $this->retrievePositiveInteger(mb_substr($hex, 4, $Rl * 2, '8bit'));
        $R = str_pad($R, $partLength, '0', STR_PAD_LEFT);

        $hex = mb_substr($hex, 4 + $Rl * 2, null, '8bit');
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new DataSigningException('Invalid signature provided');
        }
        $Sl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $S = $this->retrievePositiveInteger(mb_substr($hex, 4, $Sl * 2, '8bit'));
        $S = str_pad($S, $partLength, '0', STR_PAD_LEFT);

        return pack('H*', $R.$S);
    }

    /**
     * The code is a copy/paste from another lib (web-token/jwt-core) which is not compatible with php <= 7.0.
     *
     * @see https://github.com/web-token/jwt-core/blob/master/Util/ECSignature.php
     */
    private function preparePositiveInteger($data)
    {
        if (mb_substr($data, 0, 2, '8bit') > '7f') {
            return '00'.$data;
        }
        while ('00' === mb_substr($data, 0, 2, '8bit') && mb_substr($data, 2, 2, '8bit') <= '7f') {
            $data = mb_substr($data, 2, null, '8bit');
        }

        return $data;
    }

    /**
     * The code is a copy/paste from another lib (web-token/jwt-core) which is not compatible with php <= 7.0.
     *
     * @see https://github.com/web-token/jwt-core/blob/master/Util/ECSignature.php
     */
    private function retrievePositiveInteger($data)
    {
        while ('00' === mb_substr($data, 0, 2, '8bit') && mb_substr($data, 2, 2, '8bit') > '7f') {
            $data = mb_substr($data, 2, null, '8bit');
        }

        return $data;
    }

    /**
     * Extract Sign Option From Jws Alg
     *
     * @param string $alg
     * @return array
     */
    public function extractSignOptionFromJWSAlg($alg)
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', $alg, $match)) {
            throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        if (!\defined('OPENSSL_ALGO_SHA' . $match[2])) {
            throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        $algorithm = \constant('OPENSSL_ALGO_SHA' . $match[2]);

        switch ($match[1]) {
            case 'RS':
                $format = static::FORMAT_DER;
                break;
            case 'ES':
                $format = static::FORMAT_ECDSA;
                break;
            default:
                throw new AcmeCoreClientException(sprintf('The given "%s" algorithm is not supported', $alg));
        }

        return [$algorithm, $format];
    }
}
