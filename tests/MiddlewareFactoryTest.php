<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Middleware\Dispatcher\ActionParametersInjector\ActionParametersInjector;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\Tests\Support\Container;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->createContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->create(TestMiddleware::class);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->createContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create([TestController::class, 'index']);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testInvalidMiddleware(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(new \stdClass());
    }

    public function testInvalidMiddlewareAddWrongString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create('test');
    }

    public function testInvalidMiddlewareAddWrongStringClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware class name or a callable.');
        $this->getMiddlewareFactory()->create(TestController::class);
    }

    public function testInvalidMiddlewareAddWrongArraySize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['test']);
    }

    public function testInvalidMiddlewareAddWrongArrayClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['class', 'test']);
    }

    public function testInvalidMiddlewareAddWrongArrayType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['class' => \Yiisoft\Router\Tests\Support\TestController::class, 'index']);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryInterface
    {
        return new MiddlewareFactory(
            $container ?? $this->createContainer(),
            $this->createActionParametersInjector()
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }

    private function createActionParametersInjector(array $parameters = []): ActionParametersInjector
    {
        return new ActionParametersInjector($parameters);
    }
}
