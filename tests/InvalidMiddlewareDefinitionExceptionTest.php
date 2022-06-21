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
                'Class `test` not found',
            ],
            [
                TestController::class,
                '"Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
                'Class `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` exists, but does not implement',
            ],
            [
                new TestController(),
                'an instance of "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
                'Related links',
            ],
            [
                [TestController::class, 'notExistsAction'],
                '["Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "notExistsAction"]',
                'Try adding `notExistsAction()` action to ' .
                '`Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` controller:',
            ],
            [
                ['class' => TestController::class, 'index'],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
                null,
            ],
            [
                ['object' => TestController::class, 'index'],
                '["object" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
                'You may have an error in array definition. Array definition validation result',
            ],
            [
                ['class' => TestController::class],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"]',
                'Array definition is valid, ' .
                'class `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` exists, ' .
                'but does not implement `Psr\Http\Server\MiddlewareInterface`.',
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
            [42, '42'],
            [[new stdClass()], '[stdClass]'],
            [true, 'true'],
            [false, 'false'],
            [
                ['class' => null, 'setValue()' => [42], 'prepare()' => []],
                '["class" => null, "setValue()" => array, ...]',
            ],
        ];
    }

    /**
     * @dataProvider dataUnknownDefinition
     *
     * @param mixed $definition
     */
    public function testUnknownDefinition($definition, string $value): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertSame(
            'Parameter should be either PSR middleware class name or a callable. Got ' . $value . '.',
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
