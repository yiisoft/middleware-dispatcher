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
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use Yiisoft\Middleware\Dispatcher\Tests\Support\Container;
use Yiisoft\Middleware\Dispatcher\Tests\Support\MockEventDispatcher;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testAddMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $request = new ServerRequest('GET', '/');

        $dispatcher = $this->getDispatcher($container)->withMiddlewares([
            function () {
                return new Response(418);
            },
        ]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $dispatcher = $this->getDispatcher()->withMiddlewares([
            static function (): ResponseInterface {
                return (new Response())->withStatus(418);
            },
        ]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableArrayMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([TestController::class => new TestController()]);
        $dispatcher = $this->getDispatcher($container)->withMiddlewares([[TestController::class, 'index']]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
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

        $dispatcher = $this->getDispatcher()->withMiddlewares([$middleware2, $middleware1]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
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

        $dispatcher = $this->getDispatcher()->withMiddlewares([$middleware2, $middleware1]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testArrayMiddlewareSuccessfulCall(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([
            TestController::class => new TestController(),
        ]);
        $dispatcher = $this->getDispatcher($container)->withMiddlewares([[TestController::class, 'index']]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testEventsAreDispatched(): void
    {
        $eventDispatcher = new MockEventDispatcher();

        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $middleware2 = static function () {
            return new Response();
        };

        $dispatcher = $this->getDispatcher(null, $eventDispatcher)->withMiddlewares([$middleware2, $middleware1]);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $this->assertEquals(
            [
                BeforeMiddleware::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterMiddleware::class,
            ],
            $eventDispatcher->getClassesEvents()
        );
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

    private function getDispatcher(ContainerInterface $container = null, ?EventDispatcherInterface $eventDispatcher = null): MiddlewareDispatcher
    {
        if ($eventDispatcher === null) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        if ($container === null) {
            return new MiddlewareDispatcher(
                new MiddlewareFactory($this->getContainer()),
                new MiddlewareStack($eventDispatcher)
            );
        }

        return new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            new MiddlewareStack($eventDispatcher)
        );
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
