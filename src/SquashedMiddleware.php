<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Generator;
use Iterator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\Exception\InvalidMiddlewareDefinitionException;

final class SquashedMiddleware implements MiddlewareInterface
{
    private ?RequestHandlerInterface $handler;

    private function __construct(
        iterable $middlewares,
        ?MiddlewareFactoryInterface $factory = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->handler = $this->createHandler($this->iterateMiddlewares($middlewares, $factory), $dispatcher);
    }

    /**
     * @param iterable $middlewares Middlewares to squash. It can be definitions for the MiddlewareFactoryInterface in
     * case when the $factory parameter specified.
     * @param MiddlewareFactoryInterface|null $factory Specify this parameter for middleware definitions resolving.
     * @param EventDispatcherInterface|null $dispatcher Specify this parameter if you need to listen related events:
     * - {@see \Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware}
     * - {@see \Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware}
     *
     * @return static
     */
    public static function create(
        iterable $middlewares,
        ?MiddlewareFactoryInterface $factory = null,
        ?EventDispatcherInterface $dispatcher = null
    ): self {
        return new self($middlewares, $factory, $dispatcher);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handler->withRequestHandler($handler)->handle($request);
    }

    /**
     * @psalm-param Iterator<mixed, MiddlewareInterface> $iterator
     */
    private function createHandler(Iterator $iterator, ?EventDispatcherInterface $dispatcher): RequestHandlerInterface
    {
        return new class(null, $iterator, $dispatcher) implements RequestHandlerInterface {
            private ?self $root;
            private ?self $nextHandler = null;

            /** @var Iterator<mixed, MiddlewareInterface>|null  */
            private ?Iterator $iterator;
            private ?MiddlewareInterface $middleware = null;
            private ?EventDispatcherInterface $dispatcher;
            private RequestHandlerInterface $fallbackHandler;

            public function __clone()
            {
                if ($this->root !== null) {
                    return;
                }
                $current = $this;
                while ($current->nextHandler !== null) {
                    $current->nextHandler = clone $current->nextHandler;
                    $current->nextHandler->root = $this;
                    $current = $current->nextHandler;
                }
            }

            final public function withRequestHandler(RequestHandlerInterface $handler): self
            {
                $new = clone $this;
                $new->fallbackHandler = $handler;
                return $new;
            }

            /**
             * @psalm-param Iterator<mixed, MiddlewareInterface> $iterator
             */
            public function __construct(?self $root, Iterator $iterator, ?EventDispatcherInterface $dispatcher)
            {
                $this->iterator = $iterator;
                $this->dispatcher = $dispatcher;
                $this->root = $root;
            }

            /**
             * @psalm-suppress PossiblyNullReference
             * @psalm-suppress PossiblyNullArgument
             */
            final public function handle(ServerRequestInterface $request): ResponseInterface
            {
                // If Middleware is not cached
                if ($this->middleware === null) {
                    $this->middleware = $this->iterator->current();
                    $this->iterator->next();
                    if ($this->iterator->valid()) {
                        $this->nextHandler = new self($this->root ?? $this, $this->iterator, $this->dispatcher);
                    }
                    $this->iterator = null;
                }
                return $this->processMiddleware($request);
            }

            private function processMiddleware(ServerRequestInterface $request): ResponseInterface
            {
                $nextHandler = $this->nextHandler ?? ($this->root ?? $this)->fallbackHandler;
                if ($this->middleware === null) {
                    return $nextHandler->handle($request);
                }
                // If the event dispatcher not exists
                if ($this->dispatcher === null) {
                    return $this->middleware->process($request, $nextHandler);
                }
                $this->dispatcher->dispatch(new BeforeMiddleware($this->middleware, $request));
                try {
                    return $response = $this->middleware->process($request, $nextHandler);
                } finally {
                    $this->dispatcher->dispatch(new AfterMiddleware($this->middleware, $response ?? null));
                }
            }

            public function __destruct()
            {
                $this->root = null;
                $this->nextHandler = null;
            }
        };
    }

    /**
     * @psalm-param iterable<mixed, mixed> $middlewares
     * @psalm-return Generator<int, MiddlewareInterface, mixed, RequestHandlerInterface>
     */
    private function iterateMiddlewares(iterable $middlewares, ?MiddlewareFactoryInterface $factory): Generator
    {
        /** @var mixed $middleware */
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                yield $middleware;
            }
            if ($factory === null) {
                // todo: create better and friendly exception
                throw new InvalidMiddlewareDefinitionException(
                    $middleware,
                    'Invalid middleware. If you pass middleware definition then pass middleware factory also.'
                );
            }
            yield $factory->create($middleware);
        }
    }

    public function __destruct()
    {
        $this->handler->__destruct();
        $this->handler = null;
    }
}
