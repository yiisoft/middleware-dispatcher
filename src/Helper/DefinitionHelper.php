<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Helper;

use function array_slice;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

final class DefinitionHelper
{
    public static function convertDefinitionToString(mixed $middlewareDefinition): string
    {
        if (is_object($middlewareDefinition)) {
            return 'an instance of "' . $middlewareDefinition::class . '"';
        }

        if (is_string($middlewareDefinition)) {
            return '"' . $middlewareDefinition . '"';
        }

        if (is_array($middlewareDefinition)) {
            $items = [];
            /** @var mixed $value */
            foreach (array_slice($middlewareDefinition, 0, 2) as $key => $value) {
                $items[] = (is_string($key) ? '"' . $key . '" => ' : '') . self::convertToString($value);
            }
            return '[' . implode(', ', $items) . (count($middlewareDefinition) > 2 ? ', ...' : '') . ']';
        }

        return self::convertToString($middlewareDefinition);
    }

    private static function convertToString(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return $value::class;
        }

        return gettype($value);
    }
}
