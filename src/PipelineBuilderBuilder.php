<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PipelineBuilderBuilder implements PipelineBuilderInterface
{
    private MiddlewareFactoryInterface $factory;

    public function __construct(MiddlewareFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function buildPipeline(iterable $middlewares, RequestHandlerInterface $fallbackHandler): RequestHandlerInterface
    {
        $iterator = $this->iterateMiddlewares($middlewares, $fallbackHandler);
        return $this->createHandler($iterator);
    }

    private function createHandler(Generator $iterator): RequestHandlerInterface
    {
        return new class ($iterator) implements RequestHandlerInterface
        {
            private ?Generator $iterator;
            private ?MiddlewareInterface $middleware = null;
            private ?RequestHandlerInterface $nextHandler = null;

            public function __construct(Generator $iterator)
            {
                $this->iterator = $iterator;
            }

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
                    $this->nextHandler = new self($this->iterator);
                    $this->iterator->next();
                    $this->iterator = null;
                }
                return $this->middleware->process($request, $this->nextHandler);
            }
        };
    }

    private function iterateMiddlewares(iterable $middlewares, RequestHandlerInterface $handler): Generator
    {
        foreach ($middlewares as $pipeDefinition) {
            yield $this->factory->create($pipeDefinition);
        }
        return $handler;
    }
}
