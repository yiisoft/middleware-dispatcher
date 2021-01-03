<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

final class MiddlewareFactory implements MiddlewareFactoryInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($middlewareDefinition): MiddlewareInterface
    {
        return $this->createMiddleware($middlewareDefinition);
    }

    /**
     * @param array|callable|string $middlewareDefinition A name of PSR-15 middleware, a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature or
     * a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable typed parameters
     * are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return MiddlewareInterface
     */
    private function createMiddleware($middlewareDefinition): MiddlewareInterface
    {
        $this->validateMiddleware($middlewareDefinition);

        if (is_string($middlewareDefinition)) {
            return $this->container->get($middlewareDefinition);
        }

        return $this->wrapCallable($middlewareDefinition);
    }

    private function wrapCallable($callback): MiddlewareInterface
    {
        if (is_array($callback) && !is_object($callback[0])) {
            [$controller, $action] = $callback;
            return new class($controller, $action, $this->container) implements MiddlewareInterface {
                private string $class;
                private string $method;
                private ContainerInterface $container;

                public function __construct(string $class, string $method, ContainerInterface $container)
                {
                    $this->class = $class;
                    $this->method = $method;
                    $this->container = $container;
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $controller = $this->container->get($this->class);
                    return (new Injector($this->container))->invoke([$controller, $this->method], [$request, $handler]);
                }
            };
        }

        return new class($callback, $this->container) implements MiddlewareInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                return $response instanceof MiddlewareInterface ? $response->process($request, $handler) : $response;
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

        if ($this->isCallable($middlewareDefinition) && (!is_array($middlewareDefinition) || !is_object($middlewareDefinition[0]))) {
            return;
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return is_object($definition)
                ? !$definition instanceof MiddlewareInterface
                : true;
        }

        return is_array($definition)
            && array_keys($definition) === [0, 1]
            && in_array(
                $definition[1],
                class_exists($definition[0]) ? get_class_methods($definition[0]) : [],
                true
            );
    }
}
