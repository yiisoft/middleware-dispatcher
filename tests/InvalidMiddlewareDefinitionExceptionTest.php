<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Middleware\Dispatcher\Exception\InvalidMiddlewareDefinitionException;
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
     */
    public function testBase($definition, string $expected): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertStringEndsWith('. Got ' . $expected . '.', $exception->getMessage());
    }

    public function dataUnknownDefinition(): array
    {
        return [
            [42],
            [[new stdClass()]],
        ];
    }

    /**
     * @dataProvider dataUnknownDefinition
     *
     * @param mixed $definition
     */
    public function testUnknownDefinition($definition): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertSame(
            'Parameter should be either PSR middleware class name or a callable.',
            $exception->getMessage()
        );
    }
}
