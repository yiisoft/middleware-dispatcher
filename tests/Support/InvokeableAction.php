<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class InvokeableAction
{
    public function __invoke(): ResponseInterface
    {
        return new Response(200, ['test' => 'yii']);
    }
}
