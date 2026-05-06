<?php

namespace DMT\AbMiddleware;

use Dflydev\FigCookies\Cookies;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AbPsrMiddleware::class)]
#[CoversClass(AbService::class)]
class AbPsrMiddlewareTest extends TestCase
{
    public function testProcessFirstRequest(): void
    {
        $testUid = '1234567890123456';

        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn($testUid);

        $middleware = new AbPsrMiddleware($mockService);

        $this->assertNotNull($testUid);
        $this->assertNotEmpty($testUid);

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $testUid) {
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($testUid, $request->getAttribute('ab-uid'));
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
        $this->assertStringContainsString($testUid, $firstCookie);
    }

    public function testProcessSubsequentRequests(): void
    {
        $testUid = '1234567890123456';

        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid', 'setUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn($testUid);

        $mockService->expects($this->once())
            ->method('setUid')
            ->with($testUid);

        $middleware = new AbPsrMiddleware($mockService);

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $testUid) {
            $cookies = Cookies::fromRequest($request);

            $this->assertTrue($cookies->has('ab-uid'));
            $this->assertEquals($testUid, $cookies->get('ab-uid')->getValue());
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($testUid, $request->getAttribute('ab-uid'));
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
                Cookies::COOKIE_HEADER => 'ab-uid=' . $testUid,
            ]
        );

        $response = $middleware->process($request, $mockHandler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('200 ok', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeader('Set-Cookie'));
        $responseCookie = $response->getHeader('Set-Cookie')[0];
        $this->assertStringContainsString($testUid, $responseCookie);
    }

    public function testOverrideWithQueryParameter(): void
    {
        $testUid = '1234567890123456';

        $mockService = $this->getMockBuilder(AbService::class)
            // ->onlyMethods(['getUid', 'setUid'])
            ->getMock();

        $mockService->expects($this->once())
            ->method('getUid')
            ->willReturn($testUid);

        $mockService->expects($this->once())
            ->method('setUid')
            ->with($testUid);

        $middleware = new AbPsrMiddleware($mockService);

        $mockHandle = function (ServerRequestInterface $request) use ($mockService, $testUid) {
            $cookies = Cookies::fromRequest($request);

            $this->assertArrayHasKey('ab-variant', $request->getQueryParams());

            $this->assertFalse($cookies->has('ab-uid'));
            $this->assertNotNull($request->getAttribute('ab-uid'));
            $this->assertEquals($testUid, $request->getAttribute('ab-uid'));
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
        ))->withQueryParams(['ab-variant' => $testUid]);

        $this->assertArrayHasKey('ab-variant', $request->getQueryParams());

        $response = $middleware->process($request, $mockHandler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('200 ok', (string)$response->getBody());
        $this->assertNotEmpty($response->getHeader('Set-Cookie'));
        $responseCookie = $response->getHeader('Set-Cookie')[0];
        $this->assertStringContainsString($testUid, $responseCookie);
    }
}
