<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\PipelineBuilderBuilder;
use Yiisoft\Middleware\Dispatcher\PipelineBuilderInterface;
use Yiisoft\Middleware\Dispatcher\Tests\Support\FailMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

final class MiddlewareDispatcherTest extends TestCase
{
    private ContainerInterface $container;
    private eventDispatcherInterface $eventDispatcher;

    public function testCallableMiddlewareCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $pipeline = $this->createDispatcher()->buildPipeline([
            static function (): ResponseInterface {
                return new Response(418);
            },
        ], $this->getRequestHandler());

        $response = $pipeline->handle($request);
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testArrayMiddlewareCall(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->createContainer([
            TestController::class => new TestController(),
        ]);
        $dispatcher = $this->createDispatcher($container)
            ->buildPipeline([[TestController::class, 'index']], $this->getRequestHandler());

        $response = $dispatcher->handle($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };

        $dispatcher = $this->createDispatcher()
            ->buildPipeline([$middleware1, $middleware2], $this->getRequestHandler());

        $response = $dispatcher->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function () {
            return new Response(403);
        };
        $middleware2 = static function () {
            return new Response(200);
        };

        $dispatcher = $this->createDispatcher()
            ->buildPipeline([$middleware1, $middleware2], $this->getRequestHandler());

        $response = $dispatcher->handle($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testEventsAreDispatched(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $middleware2 = static function () {
            return new Response();
        };

        $dispatcher = $this->createDispatcher()
            ->buildPipeline([$middleware1, $middleware2], $this->getRequestHandler());
        $dispatcher->handle($request);

        $this->assertEquals(
            [
                BeforeMiddleware::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterMiddleware::class,
            ],
            $this->eventDispatcher->getEventClasses()
        );
    }

    public function testEventsAreDispatchedWhenMiddlewareFailedWithException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Middleware failed.');

        $request = new ServerRequest('GET', '/');
        $middleware = fn () => new FailMiddleware();
        $dispatcher = $this->createDispatcher()
            ->buildPipeline([$middleware], $this->getRequestHandler());

        try {
            $dispatcher->handle($request);
        } finally {
            $this->assertEquals(
                [
                    BeforeMiddleware::class,
                    AfterMiddleware::class,
                ],
                $this->eventDispatcher->getEventClasses()
            );
        }
    }

    public function testImmutability(): void
    {
        $dispatcher = $this->createDispatcher();
        self::assertNotSame($dispatcher, $dispatcher->buildPipeline([], $this->getRequestHandler()));
    }

    public function testResetStackOnWithMiddlewares(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->createContainer([
            TestController::class => new TestController(),
            TestMiddleware::class => new TestMiddleware(),
        ]);

        $pipeline = $this
            ->createDispatcher($container)
            ->buildPipeline([[TestController::class, 'index']], $this->getRequestHandler());
        $pipeline->handle($request);

        $response1 = $pipeline->handle($request);

        $response2 = $pipeline->handle($request);

        self::assertNotSame($response1, $response2);
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function createDispatcher(
        ContainerInterface $container = null,
        EventDispatcherInterface $eventDispatcher = null
    ): PipelineBuilderInterface {
        $this->eventDispatcher = $eventDispatcher ?? new SimpleEventDispatcher();
        $this->container = $container ?? $this->createContainer();

        return new PipelineBuilderBuilder(new MiddlewareFactory($this->container), $this->eventDispatcher);
    }

    private function createContainer(array $instances = []): SimpleContainer
    {
        return $this->container = new SimpleContainer($instances);
    }
}
