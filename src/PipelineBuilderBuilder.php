<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Generator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class PipelineBuilderBuilder implements PipelineBuilderInterface
{
    private MiddlewareFactoryInterface $factory;
    private EventDispatcherInterface $dispatcher;

    public function __construct(MiddlewareFactoryInterface $factory, EventDispatcherInterface $dispatcher)
    {
        $this->factory = $factory;
        $this->dispatcher = $dispatcher;
    }

    public function buildPipeline(iterable $middlewares, RequestHandlerInterface $fallbackHandler): RequestHandlerInterface
    {
        $iterator = $this->iterateMiddlewares($middlewares, $fallbackHandler);
        return $this->createHandler($iterator);
    }

    /**
     * @psalm-param Generator<int, MiddlewareInterface, mixed, RequestHandlerInterface> $iterator
     */
    private function createHandler(Generator $iterator): RequestHandlerInterface
    {
        return new class($iterator, $this->dispatcher) implements RequestHandlerInterface {
            /** @var Generator<int, MiddlewareInterface, mixed, RequestHandlerInterface>|null  */
            private ?Generator $iterator;
            private ?MiddlewareInterface $middleware = null;
            private ?RequestHandlerInterface $nextHandler = null;
            private EventDispatcherInterface $dispatcher;

            /**
             * @psalm-param Generator<int, MiddlewareInterface, mixed, RequestHandlerInterface> $iterator
             */
            public function __construct(Generator $iterator, EventDispatcherInterface $dispatcher)
            {
                $this->iterator = $iterator;
                $this->dispatcher = $dispatcher;
            }

            /**
             * @psalm-suppress PossiblyNullReference
             * @psalm-suppress PossiblyNullArgument
             */
            final public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->middleware === null) {
                    if ($this->iterator === null) {
                        return $this->nextHandler->handle($request);
                    }
                    if (!$this->iterator->valid()) {
                        $this->nextHandler = $this->iterator->getReturn();
                        $this->iterator = null;
                        return $this->nextHandler->handle($request);
                    }
                    $this->middleware = $this->iterator->current();
                    $this->nextHandler = new self($this->iterator, $this->dispatcher);
                    $this->iterator->next();
                    $this->iterator = null;
                }
                $this->dispatcher->dispatch(new BeforeMiddleware($this->middleware, $request));
                try {
                    return $response = $this->middleware->process($request, $this->nextHandler);
                } finally {
                    $this->dispatcher->dispatch(new AfterMiddleware($this->middleware, $response ?? null));
                }
            }
        };
    }

    /**
     * @psalm-param iterable<mixed, mixed> $middlewares
     * @psalm-return Generator<int, MiddlewareInterface, mixed, RequestHandlerInterface>
     */
    private function iterateMiddlewares(iterable $middlewares, RequestHandlerInterface $handler): Generator
    {
        /** @var mixed $pipeDefinition */
        foreach ($middlewares as $pipeDefinition) {
            yield $this->factory->create($pipeDefinition);
        }
        return $handler;
    }
}
