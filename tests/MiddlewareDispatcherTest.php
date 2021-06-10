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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\SquashedMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\FailMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

final class MiddlewareDispatcherTest extends TestCase
{
    private ContainerInterface $container;
    private ?eventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createContainer();
        $this->eventDispatcher = null;
    }

    public function testCallableMiddlewareCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $pipeline = $this->groupMiddlewares([
            static function (): ResponseInterface {
                return new Response(418);
            },
        ]);

        $response = $pipeline->process($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testArrayMiddlewareCall(): void
    {
        $request = new ServerRequest('GET', '/');
        $this->createContainer([
            TestController::class => new TestController(),
        ]);
        $groupMiddleware = $this->groupMiddlewares([[TestController::class, 'index']]);

        $response = $groupMiddleware->process($request, $this->getRequestHandler());
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

        $groupMiddleware = $this->groupMiddlewares([$middleware1, $middleware2]);

        $response = $groupMiddleware->process($request, $this->getRequestHandler());
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

        $groupMiddleware = $this->groupMiddlewares([$middleware1, $middleware2]);

        $response = $groupMiddleware->process($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testEventsAreDispatched(): void
    {
        $this->initDispatcher();
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $middleware2 = static function () {
            return new Response();
        };

        $groupMiddleware = $this->groupMiddlewares([$middleware1, $middleware2]);
        $groupMiddleware->process($request, $this->getRequestHandler());

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

        $this->initDispatcher();
        $request = new ServerRequest('GET', '/');
        $middleware = fn () => new FailMiddleware();
        $groupMiddleware = $this->groupMiddlewares([$middleware]);

        try {
            $groupMiddleware->process($request, $this->getRequestHandler());
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

    public function testResetStackOnWithMiddlewares(): void
    {
        $request = new ServerRequest('GET', '/');
        $this->createContainer([
            TestController::class => new TestController(),
            TestMiddleware::class => new TestMiddleware(),
        ]);

        $pipeline = $this->groupMiddlewares([[TestController::class, 'index']]);
        $pipeline->process($request, $this->getRequestHandler());
        $handler = $this->getRequestHandler();

        $response1 = $pipeline->process($request, $handler);
        $response2 = $pipeline->process($request, $handler);

        self::assertEquals($response1, $response2);
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

    private function groupMiddlewares(iterable $middlewares): MiddlewareInterface
    {
        return SquashedMiddleware::create(
            $middlewares,
            new MiddlewareFactory($this->container),
            $this->eventDispatcher
        );
    }

    private function createContainer(array $instances = []): SimpleContainer
    {
        return $this->container = new SimpleContainer($instances);
    }

    private function initDispatcher(\Closure ...$listeners): SimpleEventDispatcher
    {
        return $this->eventDispatcher = new SimpleEventDispatcher(...$listeners);
    }
}
