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

class RepositoryWithBackupTest extends AbstractRepositoryTest
{
    protected function createRepository()
    {
        return new Repository($this->serializer, $this->master, $this->backup, true);
    }

    public function testStoreAccountKeyPair()
    {
        parent::testStoreAccountKeyPair();

        $this->assertEquals("public\n", $this->backup->read('account/key.public.pem'));
        $this->assertEquals("private\n", $this->backup->read('account/key.private.pem'));
    }

    public function testStoreDomainKeyPair()
    {
        parent::testStoreDomainKeyPair();

        $this->assertEquals("public\n", $this->backup->read('certs/example.com/private/key.public.pem'));
        $this->assertEquals("private\n", $this->backup->read('certs/example.com/private/key.private.pem'));
    }

    public function testStoreDomainDistinguishedName()
    {
        parent::testStoreDomainDistinguishedName();

        $this->assertJson($this->backup->read('var/example.com/distinguished_name.json'));
    }

    public function testStoreDomainCertificate()
    {
        parent::testStoreDomainCertificate();

        $this->assertEquals(self::$certPem."\n".self::$issuerCertPem."\nprivate\n", $this->backup->read('certs/example.com/private/combined.pem'));
        $this->assertEquals(self::$certPem."\n", $this->backup->read('certs/example.com/public/cert.pem'));
        $this->assertEquals(self::$issuerCertPem."\n", $this->backup->read('certs/example.com/public/chain.pem'));
        $this->assertEquals(self::$certPem."\n".self::$issuerCertPem."\n", $this->backup->read('certs/example.com/public/fullchain.pem'));
    }
}
