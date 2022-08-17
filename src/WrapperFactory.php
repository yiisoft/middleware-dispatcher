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
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function createCallableWrapper(callable $callback): MiddlewareInterface
    {
        return new class ($callback, $this->container) implements MiddlewareInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                /** @var mixed $response */
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

    public function createActionWrapper(string $class, string $method): MiddlewareInterface
    {
        return new class ($this->container, $class, $method) implements MiddlewareInterface {
            private string $class;
            private string $method;
            private ContainerInterface $container;

            public function __construct(ContainerInterface $container, string $class, string $method)
            {
                $this->container = $container;
                $this->class = $class;
                $this->method = $method;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                /** @var mixed $controller */
                $controller = $this->container->get($this->class);

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
