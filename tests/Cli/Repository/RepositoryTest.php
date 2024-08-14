<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Cli\Repository;

use AcmePhp\Cli\Repository\Repository;
use AcmePhp\Cli\Serializer\PemEncoder;
use AcmePhp\Cli\Serializer\PemNormalizer;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\PrivateKey;
use AcmePhp\Ssl\PublicKey;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class RepositoryTest extends TestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var Filesystem
     */
    protected $storage;

    /**
     * @var Repository
     */
    protected $repository;

    public function setUp(): void
    {
        $this->serializer = new Serializer(
            [new PemNormalizer(), new ObjectNormalizer()],
            [new PemEncoder(), new JsonEncoder()]
        );

        $this->storage = new Filesystem(new MemoryAdapter());
        $this->repository = new Repository($this->serializer, $this->storage);
    }

    public function testStoreAccountKeyPair()
    {
        $this->repository->storeAccountKeyPair(new KeyPair(new PublicKey('public'), new PrivateKey('private')));

        $this->assertEquals("public\n", $this->storage->read('account/key.public.pem'));
        $this->assertEquals("private\n", $this->storage->read('account/key.private.pem'));
    }

    public function testLoadAccountKeyPair()
    {
        $keyPair = new KeyPair(new PublicKey("public\n"), new PrivateKey("private\n"));

        $this->assertFalse($this->repository->hasAccountKeyPair());
        $this->repository->storeAccountKeyPair($keyPair);
        $this->assertTrue($this->repository->hasAccountKeyPair());
        $this->assertEquals($keyPair, $this->repository->loadAccountKeyPair());
    }

    public function testLoadAccountKeyPairFail()
    {
        $this->expectException('AcmePhp\Cli\Exception\AcmeCliException');
        $this->repository->loadAccountKeyPair();
    }

    public function testStoreDomainKeyPair()
    {
        $this->repository->storeDomainKeyPair('example.com', new KeyPair(new PublicKey('public'), new PrivateKey('private')));

        $this->assertEquals("public\n", $this->storage->read('certs/example.com/private/key.public.pem'));
        $this->assertEquals("private\n", $this->storage->read('certs/example.com/private/key.private.pem'));
    }

    public function testLoadDomainKeyPair()
    {
        $keyPair = new KeyPair(new PublicKey("public\n"), new PrivateKey("private\n"));

        $this->assertFalse($this->repository->hasDomainKeyPair('example.com'));
        $this->repository->storeDomainKeyPair('example.com', $keyPair);
        $this->assertTrue($this->repository->hasDomainKeyPair('example.com'));
        $this->assertEquals($keyPair, $this->repository->loadDomainKeyPair('example.com'));
    }

    public function testLoadDomainKeyPairFail()
    {
        $this->expectException('AcmePhp\Cli\Exception\AcmeCliException');
        $this->repository->loadDomainKeyPair('example.com');
    }

    public function testStoreDomainAuthorizationChallenge()
    {
        $challenge = new AuthorizationChallenge(
            'example.org',
            'valid',
            'http-01',
            'https://acme-v01.api.letsencrypt.org/acme/challenge/bzHDB1T3ssGlGEfK_j-sTsCz6eayLww_Eb56wQpEtCk/124845837',
            'wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA',
            'wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA.zUny8k33uiaGcQMz8rGcWJnnbuLwTCpbNc7luaPyDgY'
        );

        $this->repository->storeDomainAuthorizationChallenge('example.com', $challenge);

        $json = $this->storage->read('var/example.com/authorization_challenge.json');
        $this->assertJson($json);

        $data = json_decode($json, true);

        $this->assertEquals('example.org', $data['domain']);
        $this->assertEquals('http-01', $data['type']);
        $this->assertEquals('https://acme-v01.api.letsencrypt.org/acme/challenge/bzHDB1T3ssGlGEfK_j-sTsCz6eayLww_Eb56wQpEtCk/124845837', $data['url']);
        $this->assertEquals('wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA', $data['token']);
        $this->assertEquals('wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA.zUny8k33uiaGcQMz8rGcWJnnbuLwTCpbNc7luaPyDgY', $data['payload']);
    }

    public function testLoadDomainAuthorizationChallenge()
    {
        $challenge = new AuthorizationChallenge(
            'example.org',
            'valid',
            'http-01',
            'https://acme-v01.api.letsencrypt.org/acme/challenge/bzHDB1T3ssGlGEfK_j-sTsCz6eayLww_Eb56wQpEtCk/124845837',
            'wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA',
            'wJDbK9uuuz56O6z_dhMFStHQf4JnEYU9A8WJi7lS8MA.zUny8k33uiaGcQMz8rGcWJnnbuLwTCpbNc7luaPyDgY'
        );

        $this->assertFalse($this->repository->hasDomainAuthorizationChallenge('example.com'));
        $this->repository->storeDomainAuthorizationChallenge('example.com', $challenge);
        $this->assertTrue($this->repository->hasDomainAuthorizationChallenge('example.com'));
        $this->assertEquals($challenge, $this->repository->loadDomainAuthorizationChallenge('example.com'));
    }

    public function testLoadDomainAuthorizationChallengeFail()
    {
        $this->expectException('AcmePhp\Cli\Exception\AcmeCliException');
        $this->repository->loadDomainAuthorizationChallenge('example.com');
    }

    public function testStoreDomainDistinguishedName()
    {
        $dn = new DistinguishedName(
            'example.org',
            'France',
            'Ile de France',
            'Paris',
            'Acme',
            'PHP',
            'acmephp@example.org',
            ['sub.example.org', 'sub.example.com']
        );

        $this->repository->storeDomainDistinguishedName('example.com', $dn);

        $json = $this->storage->read('var/example.com/distinguished_name.json');
        $this->assertJson($json);

        $data = json_decode($json, true);

        $this->assertEquals('example.org', $data['commonName']);
        $this->assertEquals('France', $data['countryName']);
        $this->assertEquals('Ile de France', $data['stateOrProvinceName']);
        $this->assertEquals('Paris', $data['localityName']);
        $this->assertEquals('Acme', $data['organizationName']);
        $this->assertEquals('PHP', $data['organizationalUnitName']);
        $this->assertEquals('acmephp@example.org', $data['emailAddress']);
        $this->assertEquals(['sub.example.org', 'sub.example.com'], $data['subjectAlternativeNames']);
    }

    public function testLoadDomainDistinguishedName()
    {
        $dn = new DistinguishedName(
            'example.org',
            'France',
            'Ile de France',
            'Paris',
            'Acme',
            'PHP',
            'acmephp@example.org',
            ['sub.example.org', 'sub.example.com']
        );

        $this->assertFalse($this->repository->hasDomainDistinguishedName('example.com'));
        $this->repository->storeDomainDistinguishedName('example.com', $dn);
        $this->assertTrue($this->repository->hasDomainDistinguishedName('example.com'));
        $this->assertEquals($dn, $this->repository->loadDomainDistinguishedName('example.com'));
    }

    public function testLoadDomainDistinguishedNameFail()
    {
        $this->expectException('AcmePhp\Cli\Exception\AcmeCliException');
        $this->repository->loadDomainDistinguishedName('example.com');
    }

    public function testStoreDomainCertificate()
    {
        $cert = new Certificate(self::$certPem, new Certificate(self::$issuerCertPem));

        $this->repository->storeDomainKeyPair('example.com', new KeyPair(new PublicKey('public'), new PrivateKey('private')));
        $this->repository->storeDomainCertificate('example.com', $cert);

        $this->assertEquals(self::$certPem."\n".self::$issuerCertPem."\nprivate\n", $this->storage->read('certs/example.com/private/combined.pem'));
        $this->assertEquals(self::$certPem."\n", $this->storage->read('certs/example.com/public/cert.pem'));
        $this->assertEquals(self::$issuerCertPem."\n", $this->storage->read('certs/example.com/public/chain.pem'));
        $this->assertEquals(self::$certPem."\n".self::$issuerCertPem."\n", $this->storage->read('certs/example.com/public/fullchain.pem'));
    }

    public function testLoadDomainCertificate()
    {
        $cert = new Certificate(self::$certPem, new Certificate(self::$issuerCertPem));

        $this->assertFalse($this->repository->hasDomainCertificate('example.com'));
        $this->repository->storeDomainKeyPair('example.com', new KeyPair(new PublicKey('public'), new PrivateKey('private')));
        $this->repository->storeDomainCertificate('example.com', $cert);
        $this->assertTrue($this->repository->hasDomainCertificate('example.com'));
        $this->assertEquals($cert, $this->repository->loadDomainCertificate('example.com'));
    }

    public function testLoadDomainCertificateFail()
    {
        $this->expectException('AcmePhp\Cli\Exception\AcmeCliException');
        $this->repository->loadDomainCertificate('example.com');
    }

    protected static $certPem = '-----BEGIN CERTIFICATE-----
MIIGHDCCBQSgAwIBAgISA+tnMva4d7WZnP7Zqm0go0ZgMA0GCSqGSIb3DQEBCwUA
MEoxCzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MSMwIQYDVQQD
ExpMZXQncyBFbmNyeXB0IEF1dGhvcml0eSBYMzAeFw0xNjA0MzAyMTI2MDBaFw0x
NjA3MjkyMTI2MDBaMCcxJTAjBgNVBAMTHGRvd25sb2Fkcy50aXRvdWFuZ2Fsb3Bp
bi5jb20wggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQC9ms9bf5y2N1Wx
0AVeSkfndA5aMLTFYUVC40GZ4kKF2cIt/HWHNc7D1IcHT9+VBhM9W3oG1UOgUXjQ
2gYSnKelVWV2yo/VI4ULuUPqTESUY/SmLp+dtJM9TGDiazg+gzL8PlzzgBYSe5Pk
nazxxhAXIz+dpMKymXh/Nqf1k3qWWrtG7MsXY/ST1dbAcPPSLcTVjy2WnXUpdqWi
o08gmfVYM8e+IClwXLwWbOGNauLXEEySEJUpIZFFcEHSHNfmevKB2TQGMn9Yj3aR
wiOTLfMaRtBHLGknKEghJ6idUCUPjEQr0bLN7wnq+0EvITHOZ8lEy1i0RKiRkNPD
65kknIc2r4YjNzu2Ly7WZ709rQGdsdeqYMfsMIfZ1/aveSelW+kkFbtdld8qbyuF
3oYX38C6GpEUjvM+5XSV31BHA6dsdoCYnS76fh58h37k2PqQKqM3rmAqL1t7qbAA
BpF6lMI1fyWm2Pd0vmn9usdzF5/XH3yWfWjviwSgf7huThZ8Ot0lF+1zxnjjIZUJ
9Q1PwvJnjZJQpMI/nU+ZiX7LxCHgEhdyUK31XPnCjR7V1fHDlmgXksGT4mlV/BH7
CmOJ3mjxdNhy7k7j1gxGsQfSzlx+3yOxIvTlKkyDP/XObMOSAkqdRd0xUgs3hntS
9eEcF3VhinHDxgH+4TYTpOAxR0QydQIDAQABo4ICHTCCAhkwDgYDVR0PAQH/BAQD
AgWgMB0GA1UdJQQWMBQGCCsGAQUFBwMBBggrBgEFBQcDAjAMBgNVHRMBAf8EAjAA
MB0GA1UdDgQWBBQwQgmhhriIGf+/RfIqWzR7UaV7ADAfBgNVHSMEGDAWgBSoSmpj
BH3duubRObemRWXv86jsoTBwBggrBgEFBQcBAQRkMGIwLwYIKwYBBQUHMAGGI2h0
dHA6Ly9vY3NwLmludC14My5sZXRzZW5jcnlwdC5vcmcvMC8GCCsGAQUFBzAChiNo
dHRwOi8vY2VydC5pbnQteDMubGV0c2VuY3J5cHQub3JnLzAnBgNVHREEIDAeghxk
b3dubG9hZHMudGl0b3VhbmdhbG9waW4uY29tMIH+BgNVHSAEgfYwgfMwCAYGZ4EM
AQIBMIHmBgsrBgEEAYLfEwEBATCB1jAmBggrBgEFBQcCARYaaHR0cDovL2Nwcy5s
ZXRzZW5jcnlwdC5vcmcwgasGCCsGAQUFBwICMIGeDIGbVGhpcyBDZXJ0aWZpY2F0
ZSBtYXkgb25seSBiZSByZWxpZWQgdXBvbiBieSBSZWx5aW5nIFBhcnRpZXMgYW5k
IG9ubHkgaW4gYWNjb3JkYW5jZSB3aXRoIHRoZSBDZXJ0aWZpY2F0ZSBQb2xpY3kg
Zm91bmQgYXQgaHR0cHM6Ly9sZXRzZW5jcnlwdC5vcmcvcmVwb3NpdG9yeS8wDQYJ
KoZIhvcNAQELBQADggEBADx361nqxOCJoJA/BKMZnfj67rwmXdTLKFXLsA413Y3G
GIvMnxMxAQaAw8qXbZ6Ru5xQhxjF6biPiVHwPdT1wBF7muEji/3U/+kmIedwPIee
yUhSYSquISi/qm8OyfnaxMuqvvLDw/rQtxUyKur4HmLMGtjumHYPtCCsxoymA1mZ
n51sH1RTYE9LZS85782VaRRbmlTfqfzLfhtxv82+xncgWWRosGCOdxik5S7ApRx7
uyWhx2GAKgQhGQUXGO9+5rRqRVwkItoEEmktcZ7jV6Cx605LRKklDkkpIIES09Ke
tXPd364RovQXcWBCFs7+scqIh93pNLashSvnDgwlO+E=
-----END CERTIFICATE-----';

    protected static $issuerCertPem = '-----BEGIN CERTIFICATE-----
MIIEqDCCA5CgAwIBAgIRAJgT9HUT5XULQ+dDHpceRL0wDQYJKoZIhvcNAQELBQAw
PzEkMCIGA1UEChMbRGlnaXRhbCBTaWduYXR1cmUgVHJ1c3QgQ28uMRcwFQYDVQQD
Ew5EU1QgUm9vdCBDQSBYMzAeFw0xNTEwMTkyMjMzMzZaFw0yMDEwMTkyMjMzMzZa
MEoxCzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MSMwIQYDVQQD
ExpMZXQncyBFbmNyeXB0IEF1dGhvcml0eSBYMTCCASIwDQYJKoZIhvcNAQEBBQAD
ggEPADCCAQoCggEBAJzTDPBa5S5Ht3JdN4OzaGMw6tc1Jhkl4b2+NfFwki+3uEtB
BaupnjUIWOyxKsRohwuj43Xk5vOnYnG6eYFgH9eRmp/z0HhncchpDpWRz/7mmelg
PEjMfspNdxIknUcbWuu57B43ABycrHunBerOSuu9QeU2mLnL/W08lmjfIypCkAyG
dGfIf6WauFJhFBM/ZemCh8vb+g5W9oaJ84U/l4avsNwa72sNlRZ9xCugZbKZBDZ1
gGusSvMbkEl4L6KWTyogJSkExnTA0DHNjzE4lRa6qDO4Q/GxH8Mwf6J5MRM9LTb4
4/zyM2q5OTHFr8SNDR1kFjOq+oQpttQLwNh9w5MCAwEAAaOCAZIwggGOMBIGA1Ud
EwEB/wQIMAYBAf8CAQAwDgYDVR0PAQH/BAQDAgGGMH8GCCsGAQUFBwEBBHMwcTAy
BggrBgEFBQcwAYYmaHR0cDovL2lzcmcudHJ1c3RpZC5vY3NwLmlkZW50cnVzdC5j
b20wOwYIKwYBBQUHMAKGL2h0dHA6Ly9hcHBzLmlkZW50cnVzdC5jb20vcm9vdHMv
ZHN0cm9vdGNheDMucDdjMB8GA1UdIwQYMBaAFMSnsaR7LHH62+FLkHX/xBVghYkQ
MFQGA1UdIARNMEswCAYGZ4EMAQIBMD8GCysGAQQBgt8TAQEBMDAwLgYIKwYBBQUH
AgEWImh0dHA6Ly9jcHMucm9vdC14MS5sZXRzZW5jcnlwdC5vcmcwPAYDVR0fBDUw
MzAxoC+gLYYraHR0cDovL2NybC5pZGVudHJ1c3QuY29tL0RTVFJPT1RDQVgzQ1JM
LmNybDATBgNVHR4EDDAKoQgwBoIELm1pbDAdBgNVHQ4EFgQUqEpqYwR93brm0Tm3
pkVl7/Oo7KEwDQYJKoZIhvcNAQELBQADggEBANHIIkus7+MJiZZQsY14cCoBG1hd
v0J20/FyWo5ppnfjL78S2k4s2GLRJ7iD9ZDKErndvbNFGcsW+9kKK/TnY21hp4Dd
ITv8S9ZYQ7oaoqs7HwhEMY9sibED4aXw09xrJZTC9zK1uIfW6t5dHQjuOWv+HHoW
ZnupyxpsEUlEaFb+/SCI4KCSBdAsYxAcsHYI5xxEI4LutHp6s3OT2FuO90WfdsIk
6q78OMSdn875bNjdBYAqxUp2/LEIHfDBkLoQz0hFJmwAbYahqKaLn73PAAm1X2kj
f1w8DdnkabOLGeOVcj9LQ+s67vBykx4anTjURkbqZslUEUsn2k5xeua2zUk=
-----END CERTIFICATE-----';
}
