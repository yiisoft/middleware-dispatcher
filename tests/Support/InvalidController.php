<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

final class InvalidController
{
    public function index(): int
    {
        return 200;
    }
}
