<?php

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareDispatcherInterface
{
    /**
     * Dispatch request through middleware to get response.
     *
     * @param ServerRequestInterface $request Request to pass to middleware.
     * @param RequestHandlerInterface $fallbackHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface;

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool;
}
