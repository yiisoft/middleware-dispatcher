<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareQueue implements MiddlewareQueueInterface
{
    /**
     * Contains a queue of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var RequestHandlerInterface|null queue of middleware
     */
    private ?RequestHandlerInterface $queue = null;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function build(array $middlewares, RequestHandlerInterface $fallbackHandler): MiddlewareQueueInterface
    {
        $handler = $fallbackHandler;
        $middlewares = array_reverse($middlewares);
        $firstMiddleware = array_pop($middlewares);
        foreach ($middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $new = clone $this;
        $new->queue = $this->wrap($firstMiddleware, $handler);

        return $new;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isEmpty()) {
            throw new \RuntimeException('Queue is empty.');
        }

        return $this->queue->handle($request);
    }

    public function reset(): void
    {
        $this->queue = null;
    }

    public function isEmpty(): bool
    {
        return $this->queue === null;
    }

    /**
     * Wraps handler by middlewares
     */
    private function wrap(MiddlewareInterface $middleware, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        return new class($middleware, $handler, $this->eventDispatcher) implements RequestHandlerInterface {
            private MiddlewareInterface $middleware;
            private RequestHandlerInterface $handler;
            private EventDispatcherInterface $eventDispatcher;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $handler, EventDispatcherInterface $eventDispatcher)
            {
                $this->middleware = $middleware;
                $this->handler = $handler;
                $this->eventDispatcher = $eventDispatcher;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->eventDispatcher->dispatch(new BeforeMiddleware($this->middleware, $request));

                $response = null;
                try {
                    return $response = $this->middleware->process($request, $this->handler);
                } finally {
                    $this->eventDispatcher->dispatch(new AfterMiddleware($this->middleware, $response));
                }
            }
        };
    }
}
