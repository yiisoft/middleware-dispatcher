<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareQueueInterface extends RequestHandlerInterface
{
    public function build(array $middlewares, RequestHandlerInterface $fallbackHandler): self;

    public function reset(): void;

    public function isEmpty(): bool;
}
