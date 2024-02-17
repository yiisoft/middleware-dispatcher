<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Middleware
{
    public function __construct(
        public $definition
    ) {
    }
}
