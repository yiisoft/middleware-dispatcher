<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
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
                'Class `test` not found'
            ],
            [
                TestController::class,
                '"Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
                'Class `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` exists, but not implement'
            ],
            [
                new TestController(),
                'an instance of "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
                'Related links',
            ],
            [
                [TestController::class, 'notExistsAction'],
                '["Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "notExistsAction"]',
                'Try add action `notExistsAction()` to controller '
            ],
            [
                ['class' => TestController::class, 'index'],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataBase
     *
     * @param mixed $definition
     */
    public function testBase($definition, string $expectedMessage, ?string $expectedSolution): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertStringEndsWith('. Got ' . $expectedMessage . '.', $exception->getMessage());
        if ($expectedSolution !== null) {
            self::assertStringContainsString($expectedSolution, $exception->getSolution());
        }
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

    public function testName(): void
    {
        $exception = new InvalidMiddlewareDefinitionException('test');

        self::assertSame(
            'Invalid middleware definition',
            $exception->getName()
        );
    }
}
