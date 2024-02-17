<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Middleware
{
    public function __construct(
        public $definition
    ) {
    }

}
