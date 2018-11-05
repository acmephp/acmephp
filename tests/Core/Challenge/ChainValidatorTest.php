<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge;

use AcmePhp\Core\Challenge\ChainValidator;
use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;

class ChainValidatorTest extends TestCase
{
    public function testSupports()
    {
        $mockValidator1 = $this->prophesize(ValidatorInterface::class);
        $mockValidator2 = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new ChainValidator([$mockValidator1->reveal(), $mockValidator2->reveal()]);

        $mockValidator1->supports($dummyChallenge)->willReturn(false);
        $mockValidator2->supports($dummyChallenge)->willReturn(true);
        $this->assertTrue($validator->supports($dummyChallenge));

        $mockValidator1->supports($dummyChallenge)->willReturn(false);
        $mockValidator2->supports($dummyChallenge)->willReturn(false);
        $this->assertFalse($validator->supports($dummyChallenge));
    }

    public function testIsValid()
    {
        $mockValidator1 = $this->prophesize(ValidatorInterface::class);
        $mockValidator2 = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new ChainValidator([$mockValidator1->reveal(), $mockValidator2->reveal()]);

        $mockValidator1->supports($dummyChallenge)->willReturn(false);
        $mockValidator1->isValid($dummyChallenge)->shouldNotBeCalled();
        $mockValidator2->supports($dummyChallenge)->willReturn(true);
        $mockValidator2->isValid($dummyChallenge)->willReturn(true);

        $this->assertTrue($validator->isValid($dummyChallenge));
    }
}
