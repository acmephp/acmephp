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
use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ChainValidatorTest extends TestCase
{
    use ProphecyTrait;

    public function testSupports()
    {
        $mockValidator1 = $this->prophesize(ValidatorInterface::class);
        $mockValidator2 = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();
        $solver = $this->prophesize(SolverInterface::class)->reveal();

        $validator = new ChainValidator([$mockValidator1->reveal(), $mockValidator2->reveal()]);

        $mockValidator1->supports($dummyChallenge, $solver)->willReturn(false);
        $mockValidator2->supports($dummyChallenge, $solver)->willReturn(true);
        $this->assertTrue($validator->supports($dummyChallenge, $solver));

        $mockValidator1->supports($dummyChallenge, $solver)->willReturn(false);
        $mockValidator2->supports($dummyChallenge, $solver)->willReturn(false);
        $this->assertFalse($validator->supports($dummyChallenge, $solver));
    }

    public function testIsValid()
    {
        $mockValidator1 = $this->prophesize(ValidatorInterface::class);
        $mockValidator2 = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();
        $solver = $this->prophesize(SolverInterface::class)->reveal();

        $validator = new ChainValidator([$mockValidator1->reveal(), $mockValidator2->reveal()]);

        $mockValidator1->supports($dummyChallenge, $solver)->willReturn(false);
        $mockValidator1->isValid($dummyChallenge, $solver)->shouldNotBeCalled();
        $mockValidator2->supports($dummyChallenge, $solver)->willReturn(true);
        $mockValidator2->isValid($dummyChallenge, $solver)->willReturn(true);

        $this->assertTrue($validator->isValid($dummyChallenge, $solver));
    }
}
