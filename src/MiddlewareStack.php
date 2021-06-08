<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareStack implements MiddlewarePipelineInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    private EventDispatcherInterface $eventDispatcher;

    private array $middlewares;
    private RequestHandlerInterface $fallbackHandler;

    /**
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher to use for triggering before/after middleware
     * events.
     */
    public function __construct(array $middlewares, RequestHandlerInterface $fallbackHandler, EventDispatcherInterface $eventDispatcher)
    {
        if ($middlewares === []) {
            throw new InvalidArgumentException('No middleware defined.');
        }

        $this->eventDispatcher = $eventDispatcher;
        $this->middlewares = $middlewares;
        $this->fallbackHandler = $fallbackHandler;
    }

    private function build(): MiddlewarePipelineInterface
    {
        $handler = $this->fallbackHandler;
        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $new = clone $this;
        $new->stack = $handler;

        return $new;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handle($request);
    }

    /**
     * Reset the stack.
     */
    public function reset(): void
    {
        $this->stack = null;
    }

    /**
     * Wrap handler by middlewares.
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

                try {
                    return $response = $this->middleware->process($request, $this->handler);
                } finally {
                    $this->eventDispatcher->dispatch(new AfterMiddleware($this->middleware, $response ?? null));
                }
            }
        };
    }
}
