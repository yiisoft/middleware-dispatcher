<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Middleware\Dispatcher\Attribute\Middleware;

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

    #[Middleware([
        'class' => SetHeaderMiddleware::class,
        '__construct()' => ['x-test1', 'yii1'],
    ])]
    #[Middleware([
        'class' => SetHeaderMiddleware::class,
        '__construct()' => ['x-test2', 'yii2'],
    ])]
    #[Middleware([
        'class' => SetHeaderMiddleware::class,
        '__construct()' => ['x-test3', 'yii3'],
    ])]
    public function severalMiddlewares(): ResponseInterface
    {
        return new Response(404);
    }
}
