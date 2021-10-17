<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\Tests\Support\BindRequestAttributesController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\InvalidController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->create(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create([TestController::class, 'index']);
        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->createMock(RequestHandlerInterface::class)
        );
        self::assertSame(
            'yii',
            $response->getHeaderLine('test')
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create(
            static function (): ResponseInterface {
                return (new Response())->withStatus(418);
            }
        );
        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->createMock(RequestHandlerInterface::class)
        );
        self::assertSame(
            418,
            $response->getStatusCode()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create(
            static function (): MiddlewareInterface {
                return new TestMiddleware();
            }
        );
        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->createMock(RequestHandlerInterface::class)
        );
        self::assertSame(
            '42',
            $response->getHeaderLine('test')
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([UseParamsMiddleware::class => new UseParamsMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->create(UseParamsMiddleware::class);
        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->getRequestHandler()
        );
        self::assertSame(
            'GET',
            $response->getHeaderLine('method')
        );
    }

    public function testCreateWithUseParamsController(): void
    {
        $container = $this->getContainer([UseParamsController::class => new UseParamsController()]);
        $middleware = $this->getMiddlewareFactory($container)->create([UseParamsController::class, 'index']);
        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->getRequestHandler()
        );
        self::assertSame(
            'GET',
            $response->getHeaderLine('method')
        );
    }

    public function testCreateWithBindRequestAttributesController(): void
    {
        $container = $this->getContainer(
            [BindRequestAttributesController::class => new BindRequestAttributesController()]
        );
        $middleware = $this->getMiddlewareFactory($container)
            ->create([BindRequestAttributesController::class, 'index']);
        $handler = 'handler-string';
        $response = $middleware->process(
            (new ServerRequest('GET', '/'))->withAttribute('handler', $handler),
            $this->getRequestHandler()
        );
        self::assertSame(
            $handler,
            $response->getHeaderLine('handler')
        );
    }

    public function testCreateWithBindRequestAttributesCallable(): void
    {
        $container = $this->getContainer(
            [BindRequestAttributesController::class => new BindRequestAttributesController()]
        );
        $middleware = $this->getMiddlewareFactory($container)->create(
            static function (ServerRequestInterface $serverRequest, RequestHandlerInterface $requestHandler, $handler) {
                $response = $requestHandler->handle($serverRequest);
                return $response->withStatus(200)->withHeader('handler', $handler);
            }
        );
        $handler = 'handler-string';
        $response = $middleware->process(
            (new ServerRequest('GET', '/'))->withAttribute('handler', $handler),
            $this->getRequestHandler()
        );
        self::assertSame(
            $handler,
            $response->getHeaderLine('handler')
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create(
            static function () {
                return 42;
            }
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            new ServerRequest('GET', '/'),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongInstance(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create(new stdClass());
    }

    public function testInvalidMiddlewareWithWrongString(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create('test');
    }

    public function testInvalidMiddlewareWithWrongClass(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware class name or a callable.');
        $this->getMiddlewareFactory()->create(TestController::class);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->create([InvalidController::class, 'index']);

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            new ServerRequest('GET', '/'),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongArraySize(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create(['test']);
    }

    public function testInvalidMiddlewareWithWrongArrayClass(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create(['class', 'test']);
    }

    public function testInvalidMiddlewareWithWrongArrayType(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create(['class' => TestController::class, 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithInstance(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create([new TestController(), 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->create([7, 42]);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryInterface
    {
        if ($container !== null) {
            return new MiddlewareFactory($container);
        }

        return new MiddlewareFactory($this->getContainer());
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }
}
