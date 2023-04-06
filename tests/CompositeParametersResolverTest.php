<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\Tests\Support\ParametersResolver\NameParametersResolver;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class CompositeParametersResolverTest extends TestCase
{
    public function testBase(): void
    {
        $resolver = new CompositeParametersResolver(
            new NameParametersResolver(['a' => 1, 'b' => 2]),
            new NameParametersResolver(['a' => 10, 'b' => 11, 'c' => 12, 'd' => 13]),
        );

        $container = new SimpleContainer([TestController::class => new TestController()]);
        $middleware = (new MiddlewareFactory($container, $resolver))
            ->create([TestController::class, 'compositeResolver']);

        $response = $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertSame(
            '1-2-12-13',
            $response->getReasonPhrase(),
        );
    }
}
