<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;
use Yiisoft\Middleware\Dispatcher\Exception\InvalidMiddlewareReturnTypeException;
use Yiisoft\Middleware\Dispatcher\Helper\ResponseHelper;
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;

final class InvalidMiddlewareReturnTypeExceptionTest extends TestCase
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
                'an instance of `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController`',
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
     */
    public function testBase(mixed $definition, string $expectedMessage, ?string $expectedSolution): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertStringEndsWith('. Got ' . $expectedMessage . '.', $exception->getMessage());
        if ($expectedSolution !== null) {
            self::assertStringContainsString($expectedSolution, $exception->getSolution());
        }
    }

    public function dataInvalidReturnType(): array
    {
        return [
            [fn() => 42, 42],
            [fn() => [new stdClass()], new stdClass()],
            [fn() => true, true],
            [fn() => false, false],
            [
                fn() => ['class' => null, 'setValue()' => [42], 'prepare()' => []],
                ['class' => null, 'setValue()' => [42], 'prepare()' => []],
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidReturnType
     */
    public function testUnknownDefinition(mixed $definition, mixed $result): void
    {
        $exception = new InvalidMiddlewareReturnTypeException($definition, $result);
        self::assertSame(
            sprintf(
                'Middleware an instance of `Closure` must return an instance of `%s` or `%s`, %s returned.',
                MiddlewareInterface::class,
                ResponseInterface::class,
                ResponseHelper::convertToString($result),
            ),
            $exception->getMessage()
        );
    }

    public static function dataProviderName()
    {
        yield 'null' => [null];
        yield 'string' => ['test'];
        yield 'array' => [[]];
        yield 'object' => [new stdClass()];
        yield 'int' => [42];
    }

    /**
     * @dataProvider dataProviderName
     */
    public function testName(mixed $result): void
    {
        $exception = new InvalidMiddlewareReturnTypeException('test', $result);

        self::assertSame(
            sprintf('Invalid middleware result type %s', get_debug_type($result)),
            $exception->getName()
        );
    }
}
