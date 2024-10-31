<?php

namespace DMT\AbMiddleware;

use Dflydev\FigCookies\Cookies;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(\DMT\AbMiddleware\AbPsrMiddleware::class)]
#[CoversClass(\DMT\AbMiddleware\AbService::class)]
class AbPsrMiddlewareTest extends TestCase
{
    public function testProcessFirstRequest(): void
    {
        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn('test-uid');

        $middleware = new AbPsrMiddleware($mockService);

        $uid = 'test-uid';

        $this->assertNotNull($uid);
        $this->assertNotEmpty($uid);

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $uid) {
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($uid, $request->getAttribute('ab-uid'));
            $this->assertNotNull($request->getAttribute('ab-service'));
            $this->assertSame($mockService, $request->getAttribute('ab-service'));

            return new Response(
                200,
                ['Content-Type' => 'text/plain'],
                '200 ok'
            );
        };

        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->onlyMethods(['handle'])
            ->getMock();

        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback($mockHandle);

        $request = new ServerRequest(
            'GET',
            'https://example.com',
        );

        $response = $middleware->process($request, $mockHandler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('200 ok', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeader('Set-Cookie'));
        $firstCookie = $response->getHeader('Set-Cookie')[0];
        $this->assertStringContainsString($uid, $firstCookie);
    }

    public function testProcessSubsequentRequests(): void
    {
        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid', 'setUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn('test-uid');

        $mockService->expects($this->once())
            ->method('setUid')
            ->with('test-uid');

        $middleware = new AbPsrMiddleware($mockService);

        $uid = 'test-uid';

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $uid) {
            $cookies = Cookies::fromRequest($request);

            $this->assertTrue($cookies->has('ab-uid'));
            $this->assertEquals($uid, $cookies->get('ab-uid')->getValue());
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($uid, $request->getAttribute('ab-uid'));
            $this->assertNotNull($request->getAttribute('ab-service'));
            $this->assertSame($mockService, $request->getAttribute('ab-service'));

            return new Response(
                200,
                ['Content-Type' => 'text/plain'],
                '200 ok'
            );
        };

        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->onlyMethods(['handle'])
            ->getMock();

        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback($mockHandle);

        $request = new ServerRequest(
            'GET',
            'https://example.com',
            [
                Cookies::COOKIE_HEADER => 'ab-uid=' . $uid
            ]
        );

        $response = $middleware->process($request, $mockHandler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('200 ok', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeader('Set-Cookie'));
        $responseCookie = $response->getHeader('Set-Cookie')[0];
        $this->assertStringContainsString($uid, $responseCookie);
    }

    public function testOverrideWithQueryParameter(): void
    {
        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid', 'setUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn('test-uid');

        $mockService->expects($this->once())
            ->method('setUid')
            ->with('test-uid');

        $middleware = new AbPsrMiddleware($mockService);

        $uid = 'test-uid';

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $uid) {
            $cookies = Cookies::fromRequest($request);

            $this->assertArrayHasKey('ab-variant', $request->getQueryParams());

            $this->assertFalse($cookies->has('ab-uid'));
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($uid, $request->getAttribute('ab-uid'));
            $this->assertNotNull($request->getAttribute('ab-service'));
            $this->assertSame($mockService, $request->getAttribute('ab-service'));

            return new Response(
                200,
                ['Content-Type' => 'text/plain'],
                '200 ok'
            );
        };

        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->onlyMethods(['handle'])
            ->getMock();

        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback($mockHandle);

        $request = (new ServerRequest(
            'GET',
            'https://example.com'
        ))->withQueryParams(['ab-variant' => $uid]);

        $this->assertArrayHasKey('ab-variant',$request->getQueryParams());

        $response = $middleware->process($request, $mockHandler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('200 ok', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeader('Set-Cookie'));
        $responseCookie = $response->getHeader('Set-Cookie')[0];
        $this->assertStringContainsString($uid, $responseCookie);
    }
}
