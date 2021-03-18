<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use InvalidArgumentException;

use function get_class;
use function is_array;
use function is_object;
use function is_string;

final class InvalidMiddlewareDefinitionException extends InvalidArgumentException
{
    /**
     * @param array|callable|string $middlewareDefinition
     */
    public function __construct($middlewareDefinition)
    {
        $message = 'Parameter should be either PSR middleware class name or a callable.';

        $definitionString = $this->convertDefinitionToString($middlewareDefinition);
        if ($definitionString !== null) {
            $message .= ' Got ' . $definitionString . '.';
        }

        parent::__construct($message);
    }

    /**
     * @param mixed $middlewareDefinition
     */
    private function convertDefinitionToString($middlewareDefinition): ?string
    {
        if (is_object($middlewareDefinition)) {
            return 'an instance of "' . get_class($middlewareDefinition) . '"';
        }

        if (is_string($middlewareDefinition)) {
            return '"' . $middlewareDefinition . '"';
        }

        if (is_array($middlewareDefinition)) {
            $items = $middlewareDefinition;
            foreach ($middlewareDefinition as $key => $item) {
                if (!is_string($item)) {
                    return null;
                }
            }
            array_walk(
                $items,
                /**
                 * @param mixed $item
                 * @psalm-param array-key $key
                 */
                static function (&$item, $key) {
                    $item = (string)$item;
                    $item = '"' . $item . '"';
                    if (is_string($key)) {
                        $item = '"' . $key . '" => ' . $item;
                    }
                }
            );
            return '[' . implode(', ', $items) . ']';
        }

        return null;
    }
}
