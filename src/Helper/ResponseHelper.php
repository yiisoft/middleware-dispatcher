<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Helper;

use function gettype;
use function is_array;
use function is_bool;
use function is_object;

final class ResponseHelper
{
    public static function convertToString(mixed $response): string
    {
        if (is_object($response)) {
            return sprintf('"%s" (object)', get_class($response));
        }

        if (is_array($response)) {
            $items = [];

            if (!array_is_list($response)) {
                /** @var mixed $value */
                foreach (array_keys($response) as $key) {
                    $items[] = sprintf('"%s" => ...', $key);
                }
            } else {
                $items[] = '...';
            }
            return sprintf(
                '"[%s]" (array of %d%s)',
                implode(', ', $items),
                $count = count($response),
                $count === 1 ? ' element' : ' elements'
            );
        }

        if (is_bool($response)) {
            return sprintf('"%s" (bool)', $response ? 'true' : 'false');
        }

        if (is_scalar($response)) {
            return sprintf(
                '"%s" (%s)',
                (string)$response,
                match (true) {
                    is_int($response) => 'int',
                    is_float($response) => 'float',
                    default => gettype($response)
                }
            );
        }

        return sprintf('"%s" (%s)', gettype($response), get_debug_type($response));
    }
}
