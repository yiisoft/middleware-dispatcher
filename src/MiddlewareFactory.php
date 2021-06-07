<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

use function in_array;
use function is_array;
use function is_string;

final class MiddlewareFactory implements MiddlewareFactoryInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array|callable|string $middlewareDefinition A name of PSR-15 middleware, a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature or
     * a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable typed parameters
     * are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     */
    public function create($middlewareDefinition): MiddlewareInterface
    {
        $this->validateMiddleware($middlewareDefinition);

        if (is_string($middlewareDefinition)) {
            /** @var MiddlewareInterface */
            return $this->container->get($middlewareDefinition);
        }

        return $this->wrapCallable($middlewareDefinition);
    }

    /**
     * @param array|callable $callback
     */
    private function wrapCallable($callback): MiddlewareInterface
    {
        if (is_array($callback)) {
            return new class($this->container, $callback) implements MiddlewareInterface {
                private string $class;
                private string $method;
                private ContainerInterface $container;
                private array $callback;

                public function __construct(ContainerInterface $container, array $callback)
                {
                    [$this->class , $this->method] = $callback;
                    $this->container = $container;
                    $this->callback = $callback;
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
                {
                    /** @var mixed $controller */
                    $controller = $this->container->get($this->class);

                    /** @var mixed $response */
                    $response = (new Injector($this->container))->invoke([$controller, $this->method], [$request, $next]);
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    }

                    throw new InvalidMiddlewareDefinitionException($this->callback);
                }
            };
        }

        /** @var callable $callback */

        return new class($callback, $this->container) implements MiddlewareInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
            {
                /** @var mixed $response */
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $next]);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }
                if ($response instanceof MiddlewareInterface) {
                    return $response->process($request, $next);
                }
                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }

    /**
     * @param array|callable|string $middlewareDefinition A name of PSR-15 middleware, a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature or
     * a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable typed parameters
     * are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @throws InvalidMiddlewareDefinitionException
     */
    private function validateMiddleware($middlewareDefinition): void
    {
        if (is_string($middlewareDefinition) && is_subclass_of($middlewareDefinition, MiddlewareInterface::class)) {
            return;
        }

        if ($this->isCallable($middlewareDefinition)) {
            return;
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    /**
     * @param mixed $definition
     */
    private function isCallable($definition): bool
    {
        if ($definition instanceof Closure) {
            return true;
        }

        return is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && is_string($definition[1])
            && in_array(
                $definition[1],
                class_exists($definition[0]) ? get_class_methods($definition[0]) : [],
                true
            );
    }
}
