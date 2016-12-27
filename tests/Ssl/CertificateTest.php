<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Ssl;

use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\PublicKey;

class CertificateTest extends \PHPUnit_Framework_TestCase
{
    public function test getPublicKey returns a PublicKey()
    {
        $certificate = new Certificate(
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
        );

        $publicKey = $certificate->getPublicKey();
        $this->assertInstanceOf(PublicKey::class, $publicKey);
        $this->assertEquals('58b94e38ce0088f0ec5a0c38f04bd76c', md5($publicKey->getPEM()));
    }

    public function test getPublicKeyResource returns a resource()
    {
        $certificate = new Certificate(
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
        );

        $resource = $certificate->getPublicKeyResource();

        $this->assertInternalType('resource', $resource);
    }
}
