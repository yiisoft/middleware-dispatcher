<?php

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
