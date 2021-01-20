<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;

final class InvalidMiddlewareDefinitionExceptionTest extends TestCase
{
    public function dataBase(): array
    {
        return [
            [
                'test',
                '"test"',
            ],
            [
                new TestController(),
                'an instance of "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
            ],
            [
                [TestController::class, 'notExistsAction'],
                '["Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "notExistsAction"]',
            ],
            [
                ['class' => TestController::class, 'index'],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
            ],
        ];
    }

    /**
     * @dataProvider dataBase
     *
     * @param mixed $definition
     * @param string $expected
     */
    public function testBase($definition, string $expected): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        $this->assertStringEndsWith('. Got ' . $expected . '.', $exception->getMessage());
    }
}
