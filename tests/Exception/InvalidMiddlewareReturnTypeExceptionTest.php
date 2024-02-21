<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Exception;

use Exception\AbstractInvalidMiddlewareExceptionTest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;
use Throwable;
use Yiisoft\Middleware\Dispatcher\Exception\InvalidMiddlewareReturnTypeException;
use Yiisoft\Middleware\Dispatcher\Helper\ResponseHelper;

final class InvalidMiddlewareReturnTypeExceptionTest extends AbstractInvalidMiddlewareExceptionTest
{
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

    public static function dataProviderName(): iterable
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

    protected function createException(mixed $definition): Throwable
    {
        return new InvalidMiddlewareReturnTypeException($definition, new stdClass());
    }
}
