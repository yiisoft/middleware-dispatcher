<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * `BeforeMiddleware` event is raised before executing a middleware.
 */
final class BeforeMiddleware
{
    private MiddlewareInterface $middleware;
    private ServerRequestInterface $request;

    /**
     * @param MiddlewareInterface $middleware Middleware to be executed.
     * @param ServerRequestInterface $request Request to be passed to the middleware.
     */
    public function __construct(MiddlewareInterface $middleware, ServerRequestInterface $request)
    {
        $this->middleware = $middleware;
        $this->request = $request;
    }

    /**
     * @return MiddlewareInterface Middleware to be executed.
     */
    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    /**
     * @return ServerRequestInterface Request to be passed to the middleware.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
