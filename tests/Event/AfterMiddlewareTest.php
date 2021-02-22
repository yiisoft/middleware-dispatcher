<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use PHPUnit\Framework\TestCase;

class AfterMiddlewareTest extends TestCase
{
    public function testGetMiddleware(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $event = new AfterMiddleware($middleware, null);

        self::assertSame($middleware, $event->getMiddleware());
    }

    public function testGetResponse(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $event = new AfterMiddleware($middleware, $response);

        self::assertSame($response, $event->getResponse());
    }

    public function testGetResponseNull(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $event = new AfterMiddleware($middleware, null);

        self::assertNull($event->getResponse());
    }
}
