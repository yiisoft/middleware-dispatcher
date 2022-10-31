<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

final class WrapperFactory implements WrapperFactoryInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function create($callable): MiddlewareInterface
    {
        if (is_array($callable)) {
            return $this->createActionWrapper($callable[0], $callable[1]);
        }

        return $this->createCallableWrapper($callable);
    }

    public function createCallableWrapper(callable $callback): MiddlewareInterface
    {
        return new class ($callback, $this->container) implements MiddlewareInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private ContainerInterface $container
            ) {
                $this->callback = $callback;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                /** @var MiddlewareInterface|mixed|ResponseInterface $response */
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }
                if ($response instanceof MiddlewareInterface) {
                    return $response->process($request, $handler);
                }
                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }

    private function createActionWrapper(string $class, string $method): MiddlewareInterface
    {
        return new class ($this->container, $class, $method) implements MiddlewareInterface {
            public function __construct(
                private ContainerInterface $container,
                private string $class,
                private string $method
            ) {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                /** @var mixed $controller */
                $controller = $this->container->get($this->class);

                /** @var mixed|ResponseInterface $response */
                $response = (new Injector($this->container))
                    ->invoke([$controller, $this->method], [$request, $handler]);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }

                throw new InvalidMiddlewareDefinitionException([$this->class, $this->method]);
            }

            public function __debugInfo()
            {
                return [
                    'callback' => [$this->class, $this->method],
                ];
            }
        };
    }
}
