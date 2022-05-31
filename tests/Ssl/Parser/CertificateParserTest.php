<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl\Parser;

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\ParsedCertificate;
use AcmePhp\Ssl\Parser\CertificateParser;
use PHPUnit\Framework\TestCase;

class CertificateParserTest extends TestCase
{
    /** @var CertificateParser */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new CertificateParser();
    }

    public function testParseRaiseProperException()
    {
        $this->expectException('AcmePhp\Ssl\Exception\CertificateParsingException');
        $this->service->parse(new Certificate('Not a cert'));
    }

    public function testParseReturnsInstanceOfParsedCertificate()
    {
        $result = $this->service->parse(
            new Certificate(
                '
-----BEGIN CERTIFICATE-----
MIIFkTCCBHmgAwIBAgITAP/g3ErooCmPSlx2kAVx9abKkTANBgkqhkiG9w0BAQsF
ADAfMR0wGwYDVQQDExRoYXBweSBoYWNrZXIgZmFrZSBDQTAeFw0xNjAzMjUyMjI3
MDBaFw0xNjA2MjMyMjI3MDBaMEUxFDASBgNVBAMTC2FjbWVwaHAuY29tMS0wKwYD
VQQFEyRmZmUwZGM0YWU4YTAyOThmNGE1Yzc2OTAwNTcxZjVhNmNhOTEwggIiMA0G
CSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQCYU+sxhe5q8jU2UJdzxaCUmo2MgwHP
HibHZ/RXHOmXHBGlruXgNIEiHqreXeZvxNaJhUfi4TtPmJdkYgT9Ndj+U6dgppiS
V5HstoegT0O6xO8Q7p8YA68dfymPxugZOqKU4iOennvy//Xbn3s8/V9tXe0Mqh3n
a6Fx+ysdgYo2K20zhseHs64SV1gBdxcGJFCphHpFeTQak6VDnMpBu9jT8CO5C1Pc
el1/s4KXdDjYWEzkbQH9gEQ0vSZLr7mn5+ljobSXMpKObfk+CZOFpLMFm/hTmiba
ziQ4bEZFgXySRuCQf2sHEguELB51FWm5j+5DwHAyOTIo7GnZUTMNh0nWh9WITtIw
RIXhiPLyiuJ0vnhB7NeNOCOmAAA97mYmQUVOA03VkYoZqFTjRMJs4DI8kQv9A7R+
mIzgLHGMnHlctLGyIDsfzwLRu+/PcNCqRfbeuwxGfQvXNFGw7XuszgugEgYt+/gk
1X/vlw0x2jzNwqoaZybxiZ07dfYqmDNqaqQVZ+BNs6lmDN/kcwirH0oPjSmPW/0Y
FaWFbuoZKuE7SrBxgkBjxRHdWM7/Ti1u3jRf7obbBc5sLLuxLqzhbUeH7sebDFn5
vIhF9pf9UNNBlVGTQvsyqba6eDhOAzPg6X5E8HC3HrFISJDM29teZqqURvTwm1X6
wwv/n1I8dyy5iwIDAQABo4IBnjCCAZowDgYDVR0PAQH/BAQDAgWgMB0GA1UdJQQW
MBQGCCsGAQUFBwMBBggrBgEFBQcDAjAMBgNVHRMBAf8EAjAAMB0GA1UdDgQWBBRa
A3p5XzB6inc82Zh356nV+38K3TAfBgNVHSMEGDAWgBT7eE8S+WAVgyyfF380GbMu
NupBiTBmBggrBgEFBQcBAQRaMFgwIgYIKwYBBQUHMAGGFmh0dHA6Ly8xMjcuMC4w
LjE6NDAwMi8wMgYIKwYBBQUHMAKGJmh0dHA6Ly8xMjcuMC4wLjE6NDAwMC9hY21l
L2lzc3Vlci1jZXJ0MCcGA1UdEQQgMB6CC2FjbWVwaHAuY29tgg93d3cuYWNtZXBo
cC5jb20wJwYDVR0fBCAwHjAcoBqgGIYWaHR0cDovL2V4YW1wbGUuY29tL2NybDBh
BgNVHSAEWjBYMAgGBmeBDAECATBMBgMqAwQwRTAiBggrBgEFBQcCARYWaHR0cDov
L2V4YW1wbGUuY29tL2NwczAfBggrBgEFBQcCAjATDBFEbyBXaGF0IFRob3UgV2ls
dDANBgkqhkiG9w0BAQsFAAOCAQEAsdyJaSJgXYuLE65eVQAhVBpocecmdnXHhocz
ZZ22BOxd1xVEmZ3ZlA2T9FKmcS8OtL2LnaQzEMhqT5SO4NBKsYoS5mQQWdpY69pw
Gvr9TrQQ6JZS3NJRfycc4vOzzLmkH8IO97Bsm6If+MMHEpPWQfUaAHeFGx/PIYoy
zLtghMWrqEk+UWpXnWwxzA66qXA/Q//X6QwtS39XiBkFh+GjuI5rteektrACyB35
rfA31vVhnoPUqSbuVJfL5X0a0T0dcOwL3/Vj0gAyZQAzjk1RoQ5POFQoO6QIGOKe
oVyIb1lpwK0r0vN9y8ns80MP3HtjPYtARWJ9z9P4N+guHZdnbw==
-----END CERTIFICATE-----
'
            )
        );

        $this->assertInstanceOf(ParsedCertificate::class, $result);
        $this->assertInstanceOf(Certificate::class, $result->getSource());
        $this->assertInstanceOf(\DateTime::class, $result->getValidFrom());
        $this->assertSame('20160325', $result->getValidFrom()->format('Ymd'));
        $this->assertInstanceOf(\DateTime::class, $result->getValidTo());
        $this->assertSame('20160623', $result->getValidTo()->format('Ymd'));
        $this->assertNotEmpty($result->getSerialNumber());
        $this->assertSame('acmephp.com', $result->getSubject());
        $this->assertSame(['acmephp.com', 'www.acmephp.com'], $result->getSubjectAlternativeNames());
        $this->assertSame('happy hacker fake CA', $result->getIssuer());
        $this->assertFalse($result->isSelfSigned());
    }

    public function testParseWithoutIssuerCNReturnsInstanceOfParsedCertificate()
    {
        $result = $this->service->parse(
            new Certificate(
                '
-----BEGIN CERTIFICATE-----
MIIEkjCCA3qgAwIBAgIUYOsBJUCnVkjrZKLXhz6uGeE+bfAwDQYJKoZIhvcNAQEL
BQAwgYsxCzAJBgNVBAYTAlVTMRkwFwYDVQQKExBDbG91ZEZsYXJlLCBJbmMuMTQw
MgYDVQQLEytDbG91ZEZsYXJlIE9yaWdpbiBTU0wgQ2VydGlmaWNhdGUgQXV0aG9y
aXR5MRYwFAYDVQQHEw1TYW4gRnJhbmNpc2NvMRMwEQYDVQQIEwpDYWxpZm9ybmlh
MB4XDTE2MTIxNjA2NDMwMFoXDTMxMTIxMzA2NDMwMFowYjEZMBcGA1UEChMQQ2xv
dWRGbGFyZSwgSW5jLjEdMBsGA1UECxMUQ2xvdWRGbGFyZSBPcmlnaW4gQ0ExJjAk
BgNVBAMTHUNsb3VkRmxhcmUgT3JpZ2luIENlcnRpZmljYXRlMIIBIjANBgkqhkiG
9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6PiWC1ic15fnGgSPbWKgLWyUHw2AAPDQAmNb
sr+8zYfP5GBeA7p+F0W4n6cy09E4Ofo4X5enaGcPY5xpn17HRrneu0T54jaGKVBK
17Ip088uee28WVMzirtVMn3AUHwj6LGtLnpYXWpMIXvFE7A0qGMPuoQpgiNh3Ty4
blR48vbHkbyKP1kWmL8xormueXbxPoppdmM0EhuCHCzcl3YGapTYBxq5IigEUSeM
svGTIV7xTkHp1Y1lvG913d8MLQhuBrAr+IDmwbfJsZlKrdS/QReizfaOY+aagC5L
/Ewmin1r3Z5oBtweCEprDhUZQxAjO7ixp2hRDLQxa0TtYuAnyQIDAQABo4IBFDCC
ARAwDgYDVR0PAQH/BAQDAgWgMBMGA1UdJQQMMAoGCCsGAQUFBwMBMAwGA1UdEwEB
/wQCMAAwHQYDVR0OBBYEFBtvpeo/GwVK4al5C+nC8n72lDaFMB8GA1UdIwQYMBaA
FCToU1ddfDRAh6nrlNu64RZ4/CmkMEAGCCsGAQUFBwEBBDQwMjAwBggrBgEFBQcw
AYYkaHR0cDovL29jc3AuY2xvdWRmbGFyZS5jb20vb3JpZ2luX2NhMB8GA1UdEQQY
MBaCCioubXFjZG4uY3qCCG1xY2RuLmN6MDgGA1UdHwQxMC8wLaAroCmGJ2h0dHA6
Ly9jcmwuY2xvdWRmbGFyZS5jb20vb3JpZ2luX2NhLmNybDANBgkqhkiG9w0BAQsF
AAOCAQEAQzFUegjhjwPuc4Hm5sT6u2qjujPRS+b9cMMht2SF29P/h4QMe/6hfKwk
vmRCTa5sqpzKC+paFtEPiUYcqrsn/zUKhBWpx9tWQpPFicnZLNCFcAkMuGA8xsDe
zcSWDGK5n8GCdofWVrN3dXJ9Bs6gMSqZEkfjkWaLe6PonEwjR4gT8feMBH9+QlKt
sVukLJ0Y8NynHAejoTvs2jpZbmzU5ywXCV1CQVBfbqDppZq8kqWakazxWEmYk2TY
QhYb566EvoLnFHbYvEMAM92tx9CAlPcrVA1fqbxKOJGguNdMw826e1iI7mNp2l0z
IVpfS/F2WV6y/Oh2mPNGqplqZpGjng==
-----END CERTIFICATE-----
'
            )
        );

        $this->assertInstanceOf(ParsedCertificate::class, $result);
        $this->assertInstanceOf(Certificate::class, $result->getSource());
        $this->assertInstanceOf(\DateTime::class, $result->getValidFrom());
        $this->assertSame('20161216', $result->getValidFrom()->format('Ymd'));
        $this->assertInstanceOf(\DateTime::class, $result->getValidTo());
        $this->assertSame('20311213', $result->getValidTo()->format('Ymd'));
        $this->assertNotEmpty($result->getSerialNumber());
        $this->assertNull($result->getIssuer());
        $this->assertFalse($result->isSelfSigned());
    }
}
