<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION |Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Middleware
{
    private mixed $definition;

    public function __construct(
        array|callable|string $definition
    ) {
        $this->definition = $definition;
    }

    public function getDefinition(): mixed
    {
        return $this->definition;
    }
}
