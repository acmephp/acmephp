<?php

declare(strict_types=1);

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl;

use AcmePhp\Ssl\PublicKey;
use PHPUnit\Framework\TestCase;

class PublicKeyTest extends TestCase
{
    public function testFromDERReturnsAPublicKey()
    {
        $derb64 = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAx9yClfLCWWRxnUNL1DfR
hyQAosYPDdcBaVHu+tx5p1sIsghRyvWIzn8ZA0kSvtpT+9jbPkUwcLTPLGW0SAC8
Htn4zFhhSLyrhznJbOS7eoeFF3q4m6CkVqZ856SvZs4sJuHzOpSezQXpw4L9li2C
o86/3Jd+za1esyc60PrzwynzhLY4a5FYE8ElU4uvpPY+rnbX5qSPp1B6Uj5CxWyt
3QRLFwPhNY3+1YQtVa7tvpT2ctDe2OA+SVPq2AHu7wGyF/PKh2q4fTuoIRkoFp5V
XRmjGxxNDQDLA1r88eKeGIlff6F/Y2uNvPxzkLS/1+rlBwKxPAz1fbjYtyneGZ9B
JWDioNE4T7OpPDC3v8qrjdrY+W64VMjIfS8TBQ7kBXf12HOoPKJb/F3m5BnGQsKC
BgNDqSjPIWJrCktc6if7X3ZGyCvdR2khMPgCeNaB2oO7jzCOdaJey0VfweaPPFpd
xrEU6AjWLlkRvetk2Yb1sD2E3Ah0VZ+/p6A06Oo3o/y8dy0dy2CO8Am1E7XD8W3J
U+DPwrEOIOFBs6lO6dZZ5yXrYfEyU3ROA2m5pGGI9ks7YPeqrJjrw324uUwRPchi
GkttboODHjBs7w/064iCNSh9nEurJjHaBONtuIGNXdtvF0C/iIvBv+gB7tvm/PK5
zoQL2yWlSN/1pRKChfSu6X8CAwEAAQ==';

        $publicKey = PublicKey::fromDER(base64_decode($derb64, true));

        $this->assertInstanceOf(PublicKey::class, $publicKey);
        $this->assertEquals('48fa4235a71c704c815363702d7effbb', md5($publicKey->getPEM()));
    }

    public function testGetDERReturnsAString()
    {
        $publicKey = new PublicKey('-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAx9yClfLCWWRxnUNL1DfR
hyQAosYPDdcBaVHu+tx5p1sIsghRyvWIzn8ZA0kSvtpT+9jbPkUwcLTPLGW0SAC8
Htn4zFhhSLyrhznJbOS7eoeFF3q4m6CkVqZ856SvZs4sJuHzOpSezQXpw4L9li2C
o86/3Jd+za1esyc60PrzwynzhLY4a5FYE8ElU4uvpPY+rnbX5qSPp1B6Uj5CxWyt
3QRLFwPhNY3+1YQtVa7tvpT2ctDe2OA+SVPq2AHu7wGyF/PKh2q4fTuoIRkoFp5V
XRmjGxxNDQDLA1r88eKeGIlff6F/Y2uNvPxzkLS/1+rlBwKxPAz1fbjYtyneGZ9B
JWDioNE4T7OpPDC3v8qrjdrY+W64VMjIfS8TBQ7kBXf12HOoPKJb/F3m5BnGQsKC
BgNDqSjPIWJrCktc6if7X3ZGyCvdR2khMPgCeNaB2oO7jzCOdaJey0VfweaPPFpd
xrEU6AjWLlkRvetk2Yb1sD2E3Ah0VZ+/p6A06Oo3o/y8dy0dy2CO8Am1E7XD8W3J
U+DPwrEOIOFBs6lO6dZZ5yXrYfEyU3ROA2m5pGGI9ks7YPeqrJjrw324uUwRPchi
GkttboODHjBs7w/064iCNSh9nEurJjHaBONtuIGNXdtvF0C/iIvBv+gB7tvm/PK5
zoQL2yWlSN/1pRKChfSu6X8CAwEAAQ==
-----END PUBLIC KEY-----
');

        $der = $publicKey->getDER();

        $this->assertEquals('d2ea173bab74794037c74653b65433af', md5($der));
    }

    public function testGetHPKPReturnsAString()
    {
        $publicKey = new PublicKey('-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAx9yClfLCWWRxnUNL1DfR
hyQAosYPDdcBaVHu+tx5p1sIsghRyvWIzn8ZA0kSvtpT+9jbPkUwcLTPLGW0SAC8
Htn4zFhhSLyrhznJbOS7eoeFF3q4m6CkVqZ856SvZs4sJuHzOpSezQXpw4L9li2C
o86/3Jd+za1esyc60PrzwynzhLY4a5FYE8ElU4uvpPY+rnbX5qSPp1B6Uj5CxWyt
3QRLFwPhNY3+1YQtVa7tvpT2ctDe2OA+SVPq2AHu7wGyF/PKh2q4fTuoIRkoFp5V
XRmjGxxNDQDLA1r88eKeGIlff6F/Y2uNvPxzkLS/1+rlBwKxPAz1fbjYtyneGZ9B
JWDioNE4T7OpPDC3v8qrjdrY+W64VMjIfS8TBQ7kBXf12HOoPKJb/F3m5BnGQsKC
BgNDqSjPIWJrCktc6if7X3ZGyCvdR2khMPgCeNaB2oO7jzCOdaJey0VfweaPPFpd
xrEU6AjWLlkRvetk2Yb1sD2E3Ah0VZ+/p6A06Oo3o/y8dy0dy2CO8Am1E7XD8W3J
U+DPwrEOIOFBs6lO6dZZ5yXrYfEyU3ROA2m5pGGI9ks7YPeqrJjrw324uUwRPchi
GkttboODHjBs7w/064iCNSh9nEurJjHaBONtuIGNXdtvF0C/iIvBv+gB7tvm/PK5
zoQL2yWlSN/1pRKChfSu6X8CAwEAAQ==
-----END PUBLIC KEY-----
');

        $this->assertEquals('Cc9fPJC14sLok3eyhdG7ndEb9A/RT4S22roI9U5js7Y=', $publicKey->getHPKP());
    }
}
