<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\MiddlewarePipeline;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

final class MiddlewarePipelineTest extends TestCase
{
    public function testHandleEmpty(): void
    {
        $pipeline = new MiddlewarePipeline(new SimpleEventDispatcher());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pipeline is empty.');
        $pipeline->handle($this->createMock(ServerRequestInterface::class));
    }

    public function testImmutability(): void
    {
        $pipeline = new MiddlewarePipeline(new SimpleEventDispatcher());
        self::assertNotSame($pipeline, $pipeline->build([], $this->createMock(RequestHandlerInterface::class)));
    }
}
