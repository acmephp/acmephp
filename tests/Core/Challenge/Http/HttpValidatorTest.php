<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\Http\HttpDataExtractor;
use AcmePhp\Core\Challenge\Http\HttpValidator;
use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpValidatorTest extends TestCase
{
    use ProphecyTrait;

    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockHttpClient = $this->prophesize(Client::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new HttpValidator($mockExtractor->reveal(), $mockHttpClient->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertFalse($validator->supports($stubChallenge->reveal(), $this->prophesize(SolverInterface::class)->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertTrue($validator->supports($stubChallenge->reveal(), $this->prophesize(SolverInterface::class)->reveal()));
    }

    public function testIsValid()
    {
        $checkUrl = 'http://foo.bar/.challenge';
        $checkContent = 'randomPayload';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockHttpClient = $this->prophesize(Client::class);
        $stubResponse = $this->prophesize(ResponseInterface::class);
        $stubStream = $this->prophesize(StreamInterface::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new HttpValidator($mockExtractor->reveal(), $mockHttpClient->reveal());

        $mockExtractor->getCheckUrl($stubChallenge->reveal())->willReturn($checkUrl);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockHttpClient->get($checkUrl, ['verify' => false])->willReturn($stubResponse->reveal());
        $stubResponse->getBody()->willReturn($stubStream->reveal());
        $stubStream->getContents()->willReturn($checkContent);

        $this->assertTrue($validator->isValid($stubChallenge->reveal(), $this->prophesize(SolverInterface::class)->reveal()));
    }

    public function testIsValidCatchExceptions()
    {
        $checkUrl = 'http://foo.bar/.challenge';
        $checkContent = 'randomPayload';

        $mockExtractor = $this->prophesize(HttpDataExtractor::class);
        $mockHttpClient = $this->prophesize(Client::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $validator = new HttpValidator($mockExtractor->reveal(), $mockHttpClient->reveal());

        $mockExtractor->getCheckUrl($stubChallenge->reveal())->willReturn($checkUrl);
        $mockExtractor->getCheckContent($stubChallenge->reveal())->willReturn($checkContent);

        $mockResponse = $this->prophesize(ResponseInterface::class);
        $mockResponse->getStatusCode()->willReturn(400);

        $mockHttpClient->get($checkUrl, ['verify' => false])->willThrow(new ClientException(
            'boom',
            $this->prophesize(RequestInterface::class)->reveal(),
            $mockResponse->reveal(),
        ));

        $this->assertFalse($validator->isValid($stubChallenge->reveal(), $this->prophesize(SolverInterface::class)->reveal()));
    }
}
