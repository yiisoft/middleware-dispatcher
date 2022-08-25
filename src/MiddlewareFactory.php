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
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;

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
    private WrapperFactoryInterface $wrapperFactory;

    /**
     * @param ContainerInterface $container Container to use for resolving definitions.
     */
    public function __construct(ContainerInterface $container, WrapperFactoryInterface $wrapperFactory)
    {
        $this->container = $container;
        $this->wrapperFactory = $wrapperFactory;
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
            /** @var array{0:class-string, 1:string}|Closure $middlewareDefinition */
            return $this->wrapperFactory->create($middlewareDefinition);
        }

        if ($this->isArrayDefinition($middlewareDefinition)) {
            /**
             * @psalm-var ArrayDefinitionConfig $middlewareDefinition
             *
             * @var MiddlewareInterface
             */
            return ArrayDefinition::fromConfig($middlewareDefinition)->resolve($this->container);
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
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

        try {
            DefinitionValidator::validateArrayDefinition($definition);
        } catch (InvalidConfigException $e) {
            return false;
        }

        return is_subclass_of((string) ($definition['class'] ?? ''), MiddlewareInterface::class);
    }
}
