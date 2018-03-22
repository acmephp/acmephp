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

class RepositoryWithoutBackupTest extends AbstractRepositoryTest
{
    protected function createRepository()
    {
        return new Repository($this->serializer, $this->master, $this->backup, false);
    }

    public function testStoreAccountKeyPair()
    {
        parent::testStoreAccountKeyPair();

        $this->assertFalse($this->backup->has('private/_account/public.pem'));
        $this->assertFalse($this->backup->has('private/_account/private.pem'));
    }

    public function testStoreDomainKeyPair()
    {
        parent::testStoreDomainKeyPair();

        $this->assertFalse($this->backup->has('private/example.com/public.pem'));
        $this->assertFalse($this->backup->has('private/example.com/private.pem'));
    }

    public function testStoreDomainDistinguishedName()
    {
        parent::testStoreDomainDistinguishedName();

        $this->assertFalse($this->backup->has('private/example.com/distinguished_name.json'));
    }

    public function testStoreDomainCertificate()
    {
        parent::testStoreDomainCertificate();

        $this->assertFalse($this->backup->has('certs/example.com/cert.pem'));
        $this->assertFalse($this->backup->has('certs/example.com/chain.pem'));
        $this->assertFalse($this->backup->has('certs/example.com/fullchain.pem'));
        $this->assertFalse($this->backup->has('private/example.com/combined.pem'));
    }
}
