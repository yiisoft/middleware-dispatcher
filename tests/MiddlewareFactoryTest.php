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
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\Middleware\Dispatcher\Tests\Support\InvalidController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\InvokeableAction;
use Yiisoft\Middleware\Dispatcher\Tests\Support\ParametersResolver\SimpleParametersResolver;
use Yiisoft\Middleware\Dispatcher\Tests\Support\SimpleRequestHandler;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\UseParamsMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->create(TestMiddleware::class);

        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromInvokable(): void
    {
        $container = $this->getContainer([InvokeableAction::class => new InvokeableAction()]);
        $middleware = $this->getMiddlewareFactory($container)->create(InvokeableAction::class);

        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testCreateFromRequestHandler(): void
    {
        $container = $this->getContainer([SimpleRequestHandler::class => new SimpleRequestHandler()]);
        $middleware = $this->getMiddlewareFactory($container)->create(SimpleRequestHandler::class);

        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([TestController::class, 'index']);
        $response = $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                );
        self::assertSame(
            'yii',
            $response
                ->getHeaderLine('test')
        );
        self::assertSame(
            [TestController::class, 'index'],
            $middleware->__debugInfo()['callback']
        );
    }

    public function testCreateFromArrayWithResolver(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container, new SimpleParametersResolver())
            ->create([TestController::class, 'indexWithParams']);
        $response = $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                );
        self::assertSame(
            'yii',
            $response
                ->getHeaderLine('test')
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static fn(): ResponseInterface => (new Response())->withStatus(418)
            );

        $response = $middleware
            ->process(
                $this->createMock(ServerRequestInterface::class),
                $this->createMock(RequestHandlerInterface::class)
            );

        self::assertSame(418, $response->getStatusCode());
    }

    public function testCreateFromClosureWithResolver(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container, new SimpleParametersResolver())
            ->create(
                static fn(string $test = ''): ResponseInterface => (new Response())->withStatus(418, $test)
            );
        $response = $middleware
            ->process(
                $this->createMock(ServerRequestInterface::class),
                $this->createMock(RequestHandlerInterface::class)
            );

        self::assertSame('yii', $response->getReasonPhrase());
    }

    public function testCreateCallableFromArrayWithInstance(): void
    {
        $container = $this->getContainer();
        $controller = new TestController();
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create([$controller, 'index']);

        $response = $middleware
            ->process(
                $this->createMock(ServerRequestInterface::class),
                $this->createMock(RequestHandlerInterface::class)
            );

        self::assertSame('yii', $response->getHeaderLine('test'));
        self::assertSame([TestController::class, 'index'], $middleware->__debugInfo()['callback']);
    }

    public function testCreateCallableObject(): void
    {
        $container = $this->getContainer();
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(new InvokeableAction());

        $response = $middleware
            ->process(
                $this->createMock(ServerRequestInterface::class),
                $this->createMock(RequestHandlerInterface::class)
            );

        self::assertSame('yii', $response->getHeaderLine('test'));
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static fn(): MiddlewareInterface => new TestMiddleware()
            );
        $response = $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                );
        self::assertSame(
            '42',
            $response
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
        $response = $middleware
                ->process(
                    $this->createMock(ServerRequestInterface::class),
                    $this->createMock(RequestHandlerInterface::class)
                );
        self::assertSame(
            '7',
            $response
                ->getHeaderLine('test')
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container)
            ->create(
                static fn() => 42
            );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );
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

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this
            ->getMiddlewareFactory()
            ->create([7, 42]);
    }

    /**
     * @dataProvider dataControllerMiddlewares
     */
    public function testControllerMiddleware(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container, new SimpleParametersResolver())
            ->create([TestController::class, 'error']);
        $response = $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
    }

    public static function dataControllerMiddlewares(): iterable
    {
        yield 'controller class + action name' => [
            [TestController::class, 'error'],
        ];
        yield 'controller object + action name (callable)' => [
            [new TestController(), 'error1'],
        ];
    }

    public function testMultipleControllerMiddlewares(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this
            ->getMiddlewareFactory($container, new SimpleParametersResolver())
            ->create([TestController::class, 'severalMiddlewares']);
        $response = $middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('yii1', $response->getHeaderLine('x-test1'));
        self::assertSame('yii2', $response->getHeaderLine('x-test2'));
        self::assertSame('yii3', $response->getHeaderLine('x-test3'));
    }

    private function getMiddlewareFactory(
        ContainerInterface $container = null,
        ParametersResolverInterface $parametersResolver = null
    ): MiddlewareFactory {
        if ($container !== null) {
            return new MiddlewareFactory($container, $parametersResolver);
        }

        return new MiddlewareFactory($this->getContainer(), $parametersResolver);
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
