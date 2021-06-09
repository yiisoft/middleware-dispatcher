<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Closure;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareStack implements RequestHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;
    private EventDispatcherInterface $eventDispatcher;
    private RequestHandlerInterface $fallbackHandler;
    private array $middlewares;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param RequestHandlerInterface $fallbackHandler Fallback handler
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher to use for triggering before/after middleware
     * events.
     */
    public function __construct(array $middlewares, RequestHandlerInterface $fallbackHandler, EventDispatcherInterface $eventDispatcher)
    {
        if ($middlewares === []) {
            throw new RuntimeException('Stack is empty.');
        }

        $this->middlewares = $middlewares;
        $this->fallbackHandler = $fallbackHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handle($request);
    }

    private function build(): void
    {
        $handler = $this->fallbackHandler;

        /** @var  Closure $middleware */
        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $this->stack = $handler;
    }

    /**
     * Wrap handler by middlewares.
     */
    private function wrap(Closure $middleware, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        return new class($middleware, $handler, $this->eventDispatcher) implements RequestHandlerInterface {
            private Closure $middleware;
            private RequestHandlerInterface $handler;
            private EventDispatcherInterface $eventDispatcher;

            public function __construct(
                Closure $middleware,
                RequestHandlerInterface $handler,
                EventDispatcherInterface $eventDispatcher
            ) {
                $this->middleware = $middleware;
                $this->handler = $handler;
                $this->eventDispatcher = $eventDispatcher;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var  MiddlewareInterface $middleware */
                $middleware = ($this->middleware)();
                $this->eventDispatcher->dispatch(new BeforeMiddleware($middleware, $request));

                try {
                    return $response = $middleware->process($request, $this->handler);
                } finally {
                    $this->eventDispatcher->dispatch(new AfterMiddleware($middleware, $response ?? null));
                }
            }
        };
    }
}
