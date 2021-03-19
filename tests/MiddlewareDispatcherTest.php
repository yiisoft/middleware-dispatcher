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
use Yiisoft\Middleware\Dispatcher\MiddlewarePipeline;
use Yiisoft\Middleware\Dispatcher\Tests\Support\FailMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $dispatcher = $this->createDispatcher()->add(
            static function (): ResponseInterface {
                return new Response(418);
            },
        );

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testArrayMiddlewareCall(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->createContainer([
            TestController::class => new TestController(),
        ]);
        $dispatcher = $this->createDispatcher($container)->add([TestController::class, 'index']);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareFullPipelineCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };

        $dispatcher = $this->createDispatcher()->add($middleware1)->add($middleware2);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testMiddlewarePipelineInterrupted(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = static function () {
            return new Response(403);
        };
        $middleware2 = static function () {
            return new Response(200);
        };

        $dispatcher = $this->createDispatcher()->add($middleware1)->add($middleware2);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testEventsAreDispatched(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();

        $request = new ServerRequest('GET', '/');

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $middleware2 = static function () {
            return new Response();
        };

        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->add($middleware1)->add($middleware2);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $this->assertEquals(
            [
                BeforeMiddleware::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterMiddleware::class,
            ],
            $eventDispatcher->getEventClasses()
        );
    }

    public function testEventsAreDispatchedWhenMiddlewareFailedWithException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Middleware failed.');

        $request = new ServerRequest('GET', '/');
        $eventDispatcher = new SimpleEventDispatcher();
        $middleware = fn () => new FailMiddleware();
        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->add($middleware);

        try {
            $dispatcher->dispatch($request, $this->getRequestHandler());
        } finally {
            $this->assertEquals(
                [
                    BeforeMiddleware::class,
                    AfterMiddleware::class,
                ],
                $eventDispatcher->getEventClasses()
            );
        }
    }

    public function testResetPipelineOnAdd(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->createContainer([
            TestController::class => new TestController(),
            TestMiddleware::class => new TestMiddleware(),
        ]);

        $dispatcher = $this
            ->createDispatcher($container)
            ->add([TestController::class, 'index']);
        $response = $dispatcher->dispatch($request, $this->getRequestHandler());

        self::assertSame('yii', $response->getHeaderLine('test'));

        $dispatcher = $dispatcher->add(TestMiddleware::class);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());

        self::assertSame('42', $response->getHeaderLine('test'));
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

    private function createDispatcher(ContainerInterface $container = null, ?EventDispatcherInterface $eventDispatcher = null): MiddlewareDispatcher
    {
        if ($eventDispatcher === null) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        if ($container === null) {
            return new MiddlewareDispatcher(
                new MiddlewareFactory($this->createContainer()),
                new MiddlewarePipeline($eventDispatcher)
            );
        }

        return new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            new MiddlewarePipeline($eventDispatcher)
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }
}
