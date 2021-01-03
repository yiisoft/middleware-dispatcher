<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use InvalidArgumentException;

final class InvalidMiddlewareDefinitionException extends InvalidArgumentException
{
    /**
     * @param array|callable|string $middlewareDefinition
     */
    public function __construct($middlewareDefinition)
    {
        $message = 'Parameter should be either PSR middleware class name or a callable.';

        $definition = $this->convertDefinitionToString($middlewareDefinition);
        if ($definition !== null) {
            $message .= ' Got it: ' . $definition . '.';
        }

        parent::__construct($message);
    }

    private function convertDefinitionToString($middlewareDefinition): ?string
    {
        if (is_object($middlewareDefinition)) {
            return 'Instance of class "' . get_class($middlewareDefinition) . '"';
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
            array_walk($items, function (&$item, $key) {
                $item = '"' . $item . '"';
                if (is_string($key)) {
                    $item = '"' . $key . '" => ' . $item;
                }
            });
            return '[' . implode(', ', $items) . ']';
        }

        return null;
    }
}
