<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use PHPUnit\Framework\TestCase;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

class MiddlewareStackTest extends TestCase
{
    public function testHandleEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stack is empty.');

        $stack = new MiddlewareStack([], $this->createMock(RequestHandlerInterface::class), new SimpleEventDispatcher());
        $stack->handle($this->createMock(ServerRequestInterface::class));
    }
}
