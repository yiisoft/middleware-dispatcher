<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ArrayDefinition;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;

/**
 * @psalm-import-type ArrayDefinitionConfig from ArrayDefinition
 */
final class ArrayDefinitionMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    /**
     * @psalm-var ArrayDefinitionConfig
     */
    private array $definition;

    private ?ArrayDefinitionContainer $arrayDefinitionContainer = null;

    /**
     * @psalm-param ArrayDefinitionConfig $definition
     */
    public function __construct(array $definition, ContainerInterface $container)
    {
        $this->definition = $definition;
        $this->container = $container;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $container = $this->createArrayDefinitionContainer($request, $handler);

        $definition = ArrayDefinition::fromConfig($this->definition);

        /** @var mixed $response */
        $response = $definition->resolve($container);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if ($response instanceof MiddlewareInterface) {
            return $response->process($request, $handler);
        }

        throw new InvalidMiddlewareDefinitionException($this->definition);
    }

    private function createArrayDefinitionContainer(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ArrayDefinitionContainer {
        if ($this->arrayDefinitionContainer === null) {
            $this->arrayDefinitionContainer = new ArrayDefinitionContainer($this->container);
        }

        return $this->arrayDefinitionContainer->withArguments([
            ServerRequestInterface::class => $request,
            RequestHandlerInterface::class => $handler,
        ]);
    }
}
