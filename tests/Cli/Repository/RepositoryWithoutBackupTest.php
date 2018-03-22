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

        $this->assertFalse($this->backup->has('account/key.public.pem'));
        $this->assertFalse($this->backup->has('account/key.private.pem'));
    }

    public function testStoreDomainKeyPair()
    {
        parent::testStoreDomainKeyPair();

        $this->assertFalse($this->backup->has('certs/acmephp.com/private/key.public.pem'));
        $this->assertFalse($this->backup->has('certs/acmephp.com/private/key.private.pem'));
    }

    public function testStoreDomainDistinguishedName()
    {
        parent::testStoreDomainDistinguishedName();

        $this->assertFalse($this->backup->has('var/example.com/distinguished_name.json'));
    }

    public function testStoreDomainCertificate()
    {
        parent::testStoreDomainCertificate();

        $this->assertFalse($this->backup->has('certs/example.com/private/combined.pem'));
        $this->assertFalse($this->backup->has('certs/example.com/public/cert.pem'));
        $this->assertFalse($this->backup->has('certs/example.com/public/chain.pem'));
        $this->assertFalse($this->backup->has('certs/example.com/public/fullchain.pem'));
    }
}
