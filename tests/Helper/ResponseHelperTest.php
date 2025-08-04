<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Helper;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Middleware\Dispatcher\Helper\ResponseHelper;

final class ResponseHelperTest extends TestCase
{
    public static function dataResult(): iterable
    {
        yield 'array 2' => [
            ['error' => true, 'message' => 'Error'],
            '"["error" => ..., "message" => ...]" (array of 2 elements)',
        ];
        yield 'array 1' => [
            [true],
            '"[...]" (array of 1 element)',
        ];
        yield 'string' => [
            'ok',
            '"ok" (string)',
        ];
        yield 'int' => [
            555,
            '"555" (int)',
        ];
        yield 'float' => [
            555.44,
            '"555.44" (float)',
        ];
        yield 'double' => [
            555.444444343434343434343434343434343,
            '"555.44444434343" (float)',
        ];
        yield 'bool true' => [
            true,
            '"true" (bool)',
        ];
        yield 'bool false' => [
            false,
            '"false" (bool)',
        ];
        yield 'stdClass' => [
            new stdClass(),
            '"stdClass" (object)',
        ];
    }

    /**
     * @dataProvider dataResult
     */
    public function testResult(mixed $result, string $expected): void
    {
        $this->assertSame($expected, ResponseHelper::convertToString($result));
    }
}
