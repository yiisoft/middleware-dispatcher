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
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\InvalidController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Middleware\Dispatcher\WrapperFactory;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([TestController::class, 'index']);
        self::assertSame(
            'yii',
            $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                )
                ->getHeaderLine('test')
        );
        self::assertSame(
            [TestController::class, 'index'],
            $middleware->__debugInfo()['callback']
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static function (): ResponseInterface {
                    return (new Response())->withStatus(418);
                }
            );
        self::assertSame(
            418,
            $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                )
                ->getStatusCode()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static function (): MiddlewareInterface {
                    return new TestMiddleware();
                }
            );
        self::assertSame(
            '42',
            $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                )
                ->getHeaderLine('test')
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([UseParamsMiddleware::class => new UseParamsMiddleware()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(UseParamsMiddleware::class);

        self::assertSame(
            'GET',
            $middleware
                ->process(
                    new ServerRequest('GET', '/'),
                    $this->getRequestHandler()
                )
                ->getHeaderLine('method')
        );
    }

    public function testCreateWithUseParamsController(): void
    {
        $container = $this->getContainer([UseParamsController::class => new UseParamsController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([UseParamsController::class, 'index']);

        self::assertSame(
            'GET',
            $middleware
                ->process(
                    new ServerRequest('GET', '/'),
                    $this->getRequestHandler()
                )
                ->getHeaderLine('method')
        );
    }

    public function testCreateWithArrayDefinition(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);

        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([
                'class' => TestMiddleware::class,
                'setTestValue()' => ['7'],
            ]);

        self::assertInstanceOf(TestMiddleware::class, $middleware);
        self::assertSame(
            '7',
            $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                )
                ->getHeaderLine('test')
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static function () {
                    return 42;
                }
            );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongInstance(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create(new stdClass());
    }

    public function testInvalidMiddlewareWithWrongString(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create('test');
    }

    public function testInvalidMiddlewareWithWrongClass(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware class name or a callable.');
        $this
            ->getMiddlewareFactory()
            ->create(TestController::class);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([InvalidController::class, 'index']);

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongArraySize(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create(['test']);
    }

    public function testInvalidMiddlewareWithWrongArrayClass(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create(['class', 'test']);
    }

    public function testInvalidMiddlewareWithWrongArrayType(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create(['class' => TestController::class, 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithInstance(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create([new TestController(), 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create([7, 42]);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryInterface
    {
        if ($container !== null) {
            return new MiddlewareFactory($container, new WrapperFactory($container));
        }

        return new MiddlewareFactory($this->getContainer(), new WrapperFactory($this->getContainer()));
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
