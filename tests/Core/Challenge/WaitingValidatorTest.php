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

use AcmePhp\Core\Challenge\ValidatorInterface;
use AcmePhp\Core\Challenge\WaitingValidator;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Bridge\PhpUnit\ClockMock;

class WaitingValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        ClockMock::register(WaitingValidator::class);
        ClockMock::register(static::class);
        ClockMock::withClockMock(true);
    }

    public function tearDown()
    {
        parent::tearDown();

        ClockMock::withClockMock(false);
    }

    public function testSupports()
    {
        $mockDecorated = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new WaitingValidator($mockDecorated->reveal());

        $mockDecorated->supports($dummyChallenge)->willReturn(true);
        $this->assertTrue($validator->supports($dummyChallenge));

        $mockDecorated->supports($dummyChallenge)->willReturn(false);
        $this->assertFalse($validator->supports($dummyChallenge));
    }

    public function testIsValid()
    {
        $mockDecorated = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new WaitingValidator($mockDecorated->reveal());

        $start = time();
        $mockDecorated->isValid($dummyChallenge)->willReturn(true);
        $this->assertTrue($validator->isValid($dummyChallenge));
        $this->assertLessThan(1, time() - $start);
    }

    public function testIsValidWaitBetweenTests()
    {
        $mockDecorated = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new WaitingValidator($mockDecorated->reveal());

        $start = time();
        $mockDecorated->isValid($dummyChallenge)->willReturn(false);
        $this->assertFalse($validator->isValid($dummyChallenge));
        $this->assertGreaterThanOrEqual(180, time() - $start);
    }

    public function testIsValidRetryTillOk()
    {
        $mockDecorated = $this->prophesize(ValidatorInterface::class);
        $dummyChallenge = $this->prophesize(AuthorizationChallenge::class)->reveal();

        $validator = new WaitingValidator($mockDecorated->reveal());

        $start = time();
        $mockDecorated->isValid($dummyChallenge)->willReturn(false, false, true);
        $this->assertTrue($validator->isValid($dummyChallenge));
        $this->assertGreaterThanOrEqual(6, time() - $start);
        $this->assertLessThan(9, time() - $start);
    }
}
