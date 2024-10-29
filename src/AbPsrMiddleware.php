<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AbPsrMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected AbService $abService,
        protected string $cookieName = 'ab-uid',
        protected string $cookieExpires = '+1 month',
        protected string $cookieDomain = '',
        protected string $cookiePath = '/',
        protected bool $cookieSecure = false,
        protected bool $cookieHttpOnly = true,
        protected string $cookieSameSite = 'Lax'
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = Cookies::fromRequest($request);

        if ($cookies->has($this->cookieName)) {
            $this->abService->setUid($cookies->get($this->cookieName)->getValue());
        }

        $uid = $this->abService->getUid();

        $request = $request->withAttribute('ab-service', $this->abService);
        $request = $request->withAttribute('ab-uid', $uid);

        $response = $handler->handle($request);

        $setCookie = SetCookie::create($this->cookieName, $uid)
            ->withExpires($this->cookieExpires)
            ->withDomain($this->cookieDomain)
            ->withPath($this->cookiePath)
            ->withSecure($this->cookieSecure)
            ->withHttpOnly($this->cookieHttpOnly)
            ->withSameSite(SameSite::fromString($this->cookieSameSite));

        return $response->withAddedHeader(SetCookies::SET_COOKIE_HEADER, (string)$setCookie);
    }
}
