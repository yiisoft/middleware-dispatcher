<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareDispatcherInterface
{
    public function dispatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $fallbackHandler
    ): ResponseInterface;

    public function withMiddlewares(array $middlewareDefinitions): self;

    public function hasMiddlewares(): bool;
}
