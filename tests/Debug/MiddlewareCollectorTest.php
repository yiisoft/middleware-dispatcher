<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Debug\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Middleware\Dispatcher\Debug\MiddlewareCollector;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\Tests\Support\DummyMiddleware;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class MiddlewareCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|MiddlewareCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        // before
        $collector->collect(new BeforeMiddleware($this->createCallableMiddleware(static fn() => 1), new ServerRequest('GET', '/test')));
        $collector->collect(new BeforeMiddleware($this->createCallableMiddleware([DummyMiddleware::class, 'process']), new ServerRequest('GET', '/test')));
        $collector->collect(new BeforeMiddleware($this->createCallableMiddleware('time'), new ServerRequest('GET', '/test')));

        // action
        $collector->collect(new BeforeMiddleware(new DummyMiddleware(), new ServerRequest('GET', '/test')));
        $collector->collect(new AfterMiddleware(new DummyMiddleware(), new Response(200)));

        // after
        $collector->collect(new AfterMiddleware($this->createCallableMiddleware(static fn() => 1), new Response(200)));
        $collector->collect(new AfterMiddleware($this->createCallableMiddleware([DummyMiddleware::class, 'process']), new Response(200)));
        $collector->collect(new AfterMiddleware($this->createCallableMiddleware('time'), new Response(200)));
    }

    protected function getCollector(): CollectorInterface
    {
        return new MiddlewareCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertNotEmpty($data['beforeStack']);
        $this->assertNotEmpty($data['afterStack']);
        $this->assertNotEmpty($data['actionHandler']);

        $this->assertEquals(DummyMiddleware::class, $data['actionHandler']['name']);
        $this->assertEquals('GET', $data['actionHandler']['request']->getMethod());

        $this->assertCount(3, $data['beforeStack']);
        $this->assertStringStartsWith('object(Closure)#', $data['beforeStack'][0]['name']);
        $this->assertEquals(DummyMiddleware::class . '::process', $data['beforeStack'][1]['name']);
        $this->assertEquals('{closure:time}', $data['beforeStack'][2]['name']);

        $this->assertCount(3, $data['afterStack']);
        $this->assertStringStartsWith('object(Closure)#', $data['afterStack'][0]['name']);
        $this->assertEquals(DummyMiddleware::class . '::process', $data['afterStack'][1]['name']);
        $this->assertEquals('{closure:time}', $data['afterStack'][2]['name']);
    }

    private function createCallableMiddleware(callable|array $callable): MiddlewareInterface
    {
        $factory = new MiddlewareFactory(new Container(ContainerConfig::create()));
        return $factory->create($callable);
    }
}
