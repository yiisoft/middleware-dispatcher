<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use PHPUnit\Framework\TestCase;

final class BeforeMiddlewareTest extends TestCase
{
    public function testGetMiddlewareAndRequest(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $event = new BeforeMiddleware($middleware, $request);

        self::assertSame($middleware, $event->getMiddleware());
        self::assertSame($request, $event->getRequest());
    }
}
