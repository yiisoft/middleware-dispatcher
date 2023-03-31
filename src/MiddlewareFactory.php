<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Injector\Injector;

use function in_array;
use function is_array;
use function is_string;

/**
 * Creates a PSR-15 middleware based on the definition provided.
 *
 * @psalm-import-type ArrayDefinitionConfig from ArrayDefinition
 */
final class MiddlewareFactory
{
    /**
     * @param ContainerInterface $container Container to use for resolving definitions.
     */
    public function __construct(
        private ContainerInterface $container,
        private ?ParametersResolverInterface $parametersResolver = null
    ) {
    }

    /**
     * @param array|callable|string $middlewareDefinition Middleware definition in one of the following formats:
     *
     * - A name of PSR-15 middleware class. The middleware instance will be obtained from container and executed.
     * - A callable with
     *   `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
     *   signature.
     * - Any callable.
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
    public function create(array|callable|string $middlewareDefinition): MiddlewareInterface
    {
        if ($this->isMiddlewareClassDefinition($middlewareDefinition)) {
            /** @var MiddlewareInterface */
            return $this->container->get($middlewareDefinition);
        }

        if ($this->isCallableDefinition($middlewareDefinition)) {
            return $this->wrapCallable($middlewareDefinition);
        }

        if ($this->isArrayDefinition($middlewareDefinition)) {
            /**
             * @var MiddlewareInterface
             *
             * @psalm-suppress InvalidArgument Need for Psalm version 4.* only.
             */
            return ArrayDefinition::fromConfig($middlewareDefinition)->resolve($this->container);
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    /**
     * @psalm-assert-if-true class-string<MiddlewareInterface> $definition
     */
    private function isMiddlewareClassDefinition(array|callable|string $definition): bool
    {
        return is_string($definition)
            && is_subclass_of($definition, MiddlewareInterface::class);
    }

    /**
     * @psalm-assert-if-true array{0:class-string, 1:non-empty-string}|callable $definition
     */
    private function isCallableDefinition(array|callable|string $definition): bool
    {
        if (is_callable($definition)) {
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
     * @psalm-assert-if-true ArrayDefinitionConfig $definition
     */
    private function isArrayDefinition(array|callable|string $definition): bool
    {
        if (!is_array($definition)) {
            return false;
        }

        try {
            DefinitionValidator::validateArrayDefinition($definition);
        } catch (InvalidConfigException) {
            return false;
        }

        return is_subclass_of((string)($definition['class'] ?? ''), MiddlewareInterface::class);
    }

    /**
     * @param array{0:class-string, 1:non-empty-string}|callable $callable
     */
    private function wrapCallable(array|callable $callable): MiddlewareInterface
    {
        if (is_callable($callable)) {
            return $this->createCallableWrapper($callable);
        }

        return $this->createActionWrapper($callable[0], $callable[1]);
    }

    private function createCallableWrapper(callable $callback): MiddlewareInterface
    {
        return new class ($callback, $this->container, $this->parametersResolver) implements MiddlewareInterface {
            private $callback;
            /** @var ReflectionParameter[] */
            private array $callableParameters;

            public function __construct(
                callable $callback,
                private ContainerInterface $container,
                private ?ParametersResolverInterface $parametersResolver
            ) {
                $this->callback = $callback;
                $callback = Closure::fromCallable($callback);
                $this->callableParameters = (new ReflectionFunction($callback))->getParameters();
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $parameters = [$request, $handler];
                if ($this->parametersResolver !== null) {
                    $parameters = array_merge(
                        $parameters,
                        $this->parametersResolver->resolve($this->callableParameters, $request)
                    );
                }
                /** @var MiddlewareInterface|mixed|ResponseInterface $response */
                $response = (new Injector($this->container))->invoke($this->callback, $parameters);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }
                if ($response instanceof MiddlewareInterface) {
                    return $response->process($request, $handler);
                }
                throw new InvalidMiddlewareDefinitionException($this->callback);
            }

            public function __debugInfo(): array
            {
                return ['callback' => $this->callback];
            }
        };
    }

    /**
     * @param class-string $class
     * @param non-empty-string $method
     */
    private function createActionWrapper(string $class, string $method): MiddlewareInterface
    {
        return new class ($this->container, $this->parametersResolver, $class, $method) implements MiddlewareInterface {
            /** @var ReflectionParameter[] */
            private array $actionParameters;

            public function __construct(
                private ContainerInterface $container,
                private ?ParametersResolverInterface $parametersResolver,
                /** @var class-string */
                private string $class,
                /** @var non-empty-string */
                private string $method
            ) {
                $this->actionParameters = (new ReflectionClass($this->class))
                    ->getMethod($this->method)
                    ->getParameters();
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                /** @var mixed $controller */
                $controller = $this->container->get($this->class);
                $parameters = [$request, $handler];
                if ($this->parametersResolver !== null) {
                    $parameters = array_merge(
                        $parameters,
                        $this->parametersResolver->resolve($this->actionParameters, $request)
                    );
                }

                /** @var mixed|ResponseInterface $response */
                $response = (new Injector($this->container))->invoke([$controller, $this->method], $parameters);
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
