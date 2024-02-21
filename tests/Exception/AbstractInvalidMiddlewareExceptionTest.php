<?php

declare(strict_types=1);

namespace Exception;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Middleware\Dispatcher\Tests\Support\TestController;

abstract class AbstractInvalidMiddlewareExceptionTest extends TestCase
{
    public function dataSolution(): array
    {
        return [
            [
                'test',
                'Class `test` not found',
            ],
            [
                TestController::class,
                'Class `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` exists, but does not implement',
            ],
            [
                new TestController(),
                'Related links',
            ],
            [
                [TestController::class, 'notExistsAction'],
                'Try adding `notExistsAction()` action to ' .
                '`Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` controller:',
            ],
            [
                ['object' => TestController::class, 'index'],
                'You may have an error in array definition. Array definition validation result',
            ],
            [
                ['class' => TestController::class],
                'Array definition is valid, ' .
                'class `Yiisoft\Middleware\Dispatcher\Tests\Support\TestController` exists, ' .
                'but does not implement `Psr\Http\Server\MiddlewareInterface`.',
            ],
        ];
    }

    /**
     * @dataProvider dataSolution
     */
    public function testSolution(mixed $definition, string $expectedSolution): void
    {
        $exception = static::createException($definition);
        self::assertStringContainsString($expectedSolution, $exception->getSolution());
    }

    abstract protected function createException(mixed $definition): Throwable;
}
