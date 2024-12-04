<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;
use Throwable;
use Yiisoft\Middleware\Dispatcher\Exception\InvalidMiddlewareReturnTypeException;

final class InvalidMiddlewareReturnTypeExceptionTest extends AbstractInvalidMiddlewareExceptionTest
{
    public function dataInvalidReturnType(): array
    {
        return [
            [
                'Middleware "null"',
                null,
                'null',
            ],
            [
                'Middleware ["key1" => "null"]',
                ['key1' => null],
                'null',
            ],
            [
                'Middleware ["key1" => "stdClass"]',
                ['key1' => new stdClass()],
                'null',
            ],
            [
                'Middleware ["key1" => true, "key2" => false]',
                ['key1' => true, 'key2' => false],
                'null',
            ],
            [
                'Middleware ["key1" => "null", "key2" => "null", ...]',
                ['key1' => null, 'key2' => null, 'key3' => null],
                'null',
            ],
            [
                'Middleware ["key" => "null"]',
                ['key' => null],
                'null',
            ],
            [
                'Middleware "resource"',
                fopen('php://memory', 'r'),
                'null',
            ],
            [
                'Middleware an instance of `Closure`',
                fn() => 42,
                42,
            ],
            [
                'Middleware an instance of `Closure`',
                fn() => [new stdClass()],
                new stdClass(),
            ],
            [
                'Middleware an instance of `Closure`',
                fn() => true,
                true,
            ],
            [
                'Middleware an instance of `Closure`',
                fn() => false,
                false,
            ],
            [
                'Middleware an instance of `Closure`',
                fn() => ['class' => null, 'setValue()' => [42], 'prepare()' => []],
                ['class' => null, 'setValue()' => [42], 'prepare()' => []],
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidReturnType
     */
    public function testUnknownDefinition(string $startMessage, mixed $definition, mixed $result): void
    {
        $exception = new InvalidMiddlewareReturnTypeException($definition, null);
        self::assertStringStartsWith(
            sprintf(
                '%s must return an instance of `%s` or `%s`',
                $startMessage,
                MiddlewareInterface::class,
                ResponseInterface::class,
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
