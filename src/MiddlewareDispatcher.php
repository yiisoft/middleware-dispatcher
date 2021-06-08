<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewareStack The middleware stack.
     */
    private MiddlewareStack $stack;

    private MiddlewareFactoryInterface $middlewareFactory;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $middlewareFactory, MiddlewareStack $pipeline)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->stack = $pipeline;
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param ServerRequestInterface $request Request to pass to middleware.
     * @param RequestHandlerInterface $fallbackHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $fallbackHandler
    ): ResponseInterface {
        if ($this->stack->isEmpty()) {
            $this->stack = $this->stack->build($this->buildMiddlewares(), $fallbackHandler);
        }

        return $this->stack->handle($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[]|callable[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of PSR-15 middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
     *   signature.
     * - A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For handler action and callable
     * typed parameters are automatically injected using dependency injection container.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $clone = clone $this;
        $clone->middlewareDefinitions = $middlewareDefinitions;
        $clone->stack->reset();

        return $clone;
    }

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @return MiddlewareInterface[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = $this->middlewareFactory->create($middlewareDefinition);
        }

        return $middlewares;
    }
}
