<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function get_class;
use function is_array;
use function is_object;
use function is_string;

final class InvalidMiddlewareDefinitionException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * @var mixed
     */
    private $definition;
    private ?string $definitionString;

    /**
     * @param mixed $middlewareDefinition
     */
    public function __construct($middlewareDefinition)
    {
        $this->definition = $middlewareDefinition;
        $this->definitionString = $this->convertDefinitionToString($middlewareDefinition);

        $message = 'Parameter should be either PSR middleware class name or a callable.';

        if ($this->definitionString !== null) {
            $message .= ' Got ' . $this->definitionString . '.';
        }

        parent::__construct($message);
    }

    public function getName(): string
    {
        return 'Invalid middleware definition';
    }

    public function getSolution(): ?string
    {
        $solution = [];

        if ($this->definitionString !== null) {
            $solution[] = <<<SOLUTION
            ## Got definition value

            `{$this->definitionString}`
            SOLUTION;
        }

        $suggestion = $this->generateSuggestion();
        if ($suggestion !== null) {
            $solution[] = '## Suggestion';
            $solution[] = $suggestion;
        }

        $solution[] = <<<SOLUTION
        ## Middleware definition examples

        - PSR middleware class name: `Yiisoft\Session\SessionMiddleware::class`.
        - Action in controller: `[App\Backend\UserController::class, 'index']`.

        ## Related links

        - [Callable PHP documentation](https://www.php.net/manual/language.types.callable.php)
        SOLUTION;

        return implode("\n\n", $solution);
    }

    private function generateSuggestion(): ?string
    {
        if ($this->isControllerWithNonExistAction()) {
            return <<<SOLUTION
            Class `{$this->definition[0]}` exist, but not contain method `{$this->definition[1]}()`.

            Try add action `{$this->definition[1]}()` to controller `{$this->definition[0]}`:

            ```php
            public function {$this->definition[1]}(): ResponseInterface
            {
                // TODO: Implement you action
            }
            ```
            SOLUTION;
        }

        if ($this->isNotMiddlewareClassName()) {
            return sprintf(
                'Class `%s` exists, but not implement `%s`.',
                $this->definition,
                MiddlewareInterface::class
            );
        }

        if ($this->isStringNotClassName()) {
            return sprintf(
                'Class `%s` not found. It may be need to install a package with this middleware.',
                $this->definition
            );
        }

        return null;
    }

    /**
     * @psalm-assert-if-true string $this->definition
     */
    private function isStringNotClassName(): bool
    {
        return is_string($this->definition)
            && !class_exists($this->definition);
    }

    /**
     * @psalm-assert-if-true class-string $this->definition
     */
    private function isNotMiddlewareClassName(): bool
    {
        return is_string($this->definition)
            && class_exists($this->definition);
    }

    /**
     * @psalm-assert-if-true array{0:class-string,1:string} $this->definition
     */
    private function isControllerWithNonExistAction(): bool
    {
        return is_array($this->definition)
            && array_keys($this->definition) === [0, 1]
            && is_string($this->definition[0])
            && class_exists($this->definition[0]);
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
            foreach ($middlewareDefinition as $item) {
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
            /** @var string[] $items */
            return '[' . implode(', ', $items) . ']';
        }

        return null;
    }
}
