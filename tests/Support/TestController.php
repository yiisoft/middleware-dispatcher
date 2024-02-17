<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Middleware\Dispatcher\Middleware;

final class TestController
{
    public function index(): ResponseInterface
    {
        return new Response(200, ['test' => 'yii']);
    }

    public function indexWithParams(string $test = ''): ResponseInterface
    {
        return new Response(200, ['test' => $test]);
    }

    public function compositeResolver(int $a = 0, int $b = 0, int $c = 0, int $d = 0): ResponseInterface
    {
        return new Response(
            reason: $a . '-' . $b . '-' . $c . '-' . $d,
        );
    }

    #[Middleware([
        'class' => ResponseMiddleware::class,
        '__construct()' => [200],
    ])]
    public function error(): ResponseInterface
    {
        return new Response(404);
    }
}
