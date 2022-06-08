<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ArrayDefinition;

use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * @internal
 */
final class ArrayDefinitionContainer implements ContainerInterface
{
    private ContainerInterface $container;

    /**
     * @psalm-var array<string,mixed>
     */
    private array $arguments = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @psalm-param array<string,mixed> $arguments
     */
    public function withArguments(array $arguments): self
    {
        $new = clone $this;
        $new->arguments = $arguments;
        return $new;
    }

    public function get(string $id)
    {
        return $this->arguments[$id] ?? $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->arguments) || $this->container->has($id);
    }
}
