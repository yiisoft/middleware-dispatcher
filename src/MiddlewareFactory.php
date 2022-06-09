<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Injector\Injector;
use Yiisoft\Middleware\Dispatcher\ArrayDefinition\ArrayDefinitionMiddleware;

use function in_array;
use function is_array;
use function is_string;

/**
 * Creates a PSR-15 middleware based on the definition provided.
 *
 * @psalm-import-type ArrayDefinitionConfig from ArrayDefinition
 */
final class MiddlewareFactory implements MiddlewareFactoryInterface
{
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container Container to use for resolving definitions.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array|callable|string $middlewareDefinition Middleware definition in one of the following formats:
     *
     * - A name of PSR-15 middleware class. The middleware instance will be obtained from container and executed.
     * - A callable with
     *   `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
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
     * @throws InvalidMiddlewareDefinitionException
     */
    public function create($middlewareDefinition): MiddlewareInterface
    {
        if ($this->isMiddlewareClassDefinition($middlewareDefinition)) {
            /** @var MiddlewareInterface */
            return $this->container->get($middlewareDefinition);
        }

        if ($this->isCallableDefinition($middlewareDefinition)) {
            return $this->wrapCallableDefinition($middlewareDefinition);
        }

        if ($this->isArrayDefinition($middlewareDefinition)) {
            /** @psalm-var ArrayDefinitionConfig $middlewareDefinition */
            return new ArrayDefinitionMiddleware($middlewareDefinition, $this->container);
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    /**
     * @param array|Closure $callback
     */
    private function wrapCallableDefinition($callback): MiddlewareInterface
    {
        if (is_array($callback)) {
            return new class ($this->container, $callback) implements MiddlewareInterface {
                private string $class;
                private string $method;
                private ContainerInterface $container;
                private array $callback;

                public function __construct(ContainerInterface $container, array $callback)
                {
                    [$this->class, $this->method] = $callback;
                    $this->container = $container;
                    $this->callback = $callback;
                }

                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    /** @var mixed $controller */
                    $controller = $this->container->get($this->class);

                    /** @var mixed $response */
                    $response = (new Injector($this->container))
                        ->invoke([$controller, $this->method], [$request, $handler]);
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    }

                    throw new InvalidMiddlewareDefinitionException($this->callback);
                }

                public function __debugInfo()
                {
                    return [
                        'callback' => $this->callback,
                    ];
                }
            };
        }

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

    /**
     * @param mixed $definition
     *
     * @psalm-assert-if-true class-string<MiddlewareInterface> $definition
     */
    private function isMiddlewareClassDefinition($definition): bool
    {
        return is_string($definition)
            && is_subclass_of($definition, MiddlewareInterface::class);
    }

    /**
     * @param mixed $definition
     *
     * @psalm-assert-if-true array|Closure $definition
     */
    private function isCallableDefinition($definition): bool
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

    /**
     * @param mixed $definition
     *
     * @psalm-assert-if-true ArrayDefinitionConfig $definition
     */
    private function isArrayDefinition($definition): bool
    {
        if (!is_array($definition)) {
            return false;
        }

        $class = $definition['class'] ?? null;
        if (!is_string($class)) {
            return false;
        }

        return is_subclass_of($class, MiddlewareInterface::class);
    }
}
