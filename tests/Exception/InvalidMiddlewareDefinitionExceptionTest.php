<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Exception;

use Exception\AbstractInvalidMiddlewareExceptionTest;
use Throwable;
use Yiisoft\Middleware\Dispatcher\InvalidMiddlewareDefinitionException;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;

final class InvalidMiddlewareDefinitionExceptionTest extends AbstractInvalidMiddlewareExceptionTest
{
    public function dataExceptionMessage(): array
    {
        return [
            [
                'test',
                '"test"',
            ],
            [
                TestController::class,
                '"Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"',
            ],
            [
                new TestController(),
                'an instance of `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController`',
            ],
            [
                [TestController::class, 'notExistsAction'],
                '["Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "notExistsAction"]',
            ],
            [
                ['class' => TestController::class, 'index'],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
            ],
            [
                ['object' => TestController::class, 'index'],
                '["object" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController", "index"]',
            ],
            [
                ['class' => TestController::class],
                '["class" => "Yiisoft\Middleware\Dispatcher\Tests\Support\TestController"]',
            ],
        ];
    }

    /**
     * @dataProvider dataExceptionMessage
     */
    public function testExceptionMessage(mixed $definition, string $expectedMessage): void
    {
        $exception = self::createException($definition);
        self::assertStringEndsWith('. Got ' . $expectedMessage . '.', $exception->getMessage());
    }

    public function testName(): void
    {
        $exception = new InvalidMiddlewareDefinitionException('test');

        self::assertSame(
            'Invalid middleware definition',
            $exception->getName()
        );
    }

    protected function createException(mixed $definition): Throwable
    {
        return new InvalidMiddlewareDefinitionException($definition);
    }
}
