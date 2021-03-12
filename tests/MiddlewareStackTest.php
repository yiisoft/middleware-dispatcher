<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use PHPUnit\Framework\TestCase;
use Yiisoft\Middleware\Dispatcher\Tests\Support\FailMiddleware;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

class MiddlewareStackTest extends TestCase
{
    public function testHandleEmpty(): void
    {
        $stack = new MiddlewareStack(new SimpleEventDispatcher());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stack is empty.');
        $stack->handle($this->createMock(ServerRequestInterface::class));
    }

    public function testImmutability(): void
    {
        $stack = new MiddlewareStack(new SimpleEventDispatcher());
        self::assertNotSame($stack, $stack->build([], $this->createMock(RequestHandlerInterface::class)));
    }

    public function testMiddlewareFailWithException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware failed.');

        $afterMiddlewareFired = false;
        $listener = function ($e) use (&$afterMiddlewareFired) {
            $afterMiddlewareFired = $e instanceof AfterMiddleware;
        };
        $eventDispatcher = new SimpleEventDispatcher($listener);
        $stack = new MiddlewareStack($eventDispatcher);
        $stack = $stack->build([new FailMiddleware()], $this->createMock(RequestHandlerInterface::class));
        try {
            $stack->handle($this->createMock(ServerRequestInterface::class));
        } finally {
            $this->assertTrue($afterMiddlewareFired);
        }
    }
}
