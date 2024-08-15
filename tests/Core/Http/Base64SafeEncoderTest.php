<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Http;

use AcmePhp\Core\Http\Base64SafeEncoder;
use PHPUnit\Framework\TestCase;

class Base64SafeEncoderTest extends TestCase
{
    /**
     * @dataProvider getTestVectors
     *
     * @param string $message
     * @param string $expected
     */
    public function testEncodeAndDecode($message, $expected)
    {
        $encoder = new Base64SafeEncoder();

        $encoded = $encoder->encode($message);
        $decoded = $encoder->decode($expected);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($message, $decoded);
    }

    /**
     * @see https://tools.ietf.org/html/rfc4648#section-10
     */
    public function getTestVectors()
    {
        return array(
            array(
                '000000', 'MDAwMDAw',
            ),
            array(
                "\0\0\0\0", 'AAAAAA',
            ),
            array(
                "\xff", '_w',
            ),
            array(
                "\xff\xff", '__8',
            ),
            array(
                "\xff\xff\xff", '____',
            ),
            array(
                "\xff\xff\xff\xff", '_____w',
            ),
            array(
                "\xfb", '-w',
            ),
            array(
                '', '',
            ),
            array(
                'foo', 'Zm9v',
            ),
            array(
                'foobar', 'Zm9vYmFy',
            ),
        );
    }

    /**
     * @dataProvider getTestBadVectors
     *
     * @param string $input
     */
    public function testBadInput($input)
    {
        $encoder = new Base64SafeEncoder();
        $decoded = $encoder->decode($input);
        $this->assertEquals("\00", $decoded);
    }

    public function getTestBadVectors()
    {
        return array(
            array(
                ' AA',
            ),
            array(
                "\tAA",
            ),
            array(
                "\rAA",
            ),
            array(
                "\nAA",
            ),
        );
    }
}
