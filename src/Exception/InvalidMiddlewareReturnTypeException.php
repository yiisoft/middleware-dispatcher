<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Exception;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Middleware\Dispatcher\Helper\DefinitionHelper;

use Yiisoft\Middleware\Dispatcher\Helper\ResponseHelper;

use function is_array;

final class InvalidMiddlewareReturnTypeException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    private readonly string $definitionString;

    public function __construct(
        private mixed $definition,
        private readonly mixed $result,
    ) {
        $this->definitionString = DefinitionHelper::convertDefinitionToString($definition);

        parent::__construct(
            sprintf(
                'Middleware %s must return an instance of `%s` or `%s`, %s returned.',
                $this->definitionString,
                MiddlewareInterface::class,
                ResponseInterface::class,
                ResponseHelper::convertToString($this->result)
            )
        );
    }

    public function getName(): string
    {
        return sprintf('Invalid middleware result type %s', get_debug_type($this->result));
    }

    public function getSolution(): ?string
    {
        $solution = [
            <<<SOLUTION
            ## Got definition value

            `{$this->definitionString}`
            SOLUTION
        ];

        $suggestion = $this->generateSuggestion();
        if ($suggestion !== null) {
            $solution[] = '## Suggestion';
            $solution[] = $suggestion;
        }

        $solution[] = <<<SOLUTION
        ## Middleware definition examples

        PSR middleware class name:

        ```php
        Yiisoft\Session\SessionMiddleware::class
        ```

        PSR middleware array definition:

        ```php
        [
            'class' => MyMiddleware::class,
            '__construct()' => [
                'someVar' => 42,
            ],
        ]
        ```

        Closure that returns `ResponseInterface`:

        ```php
        static function (): ResponseInterface {
            return new Response(418);
        },
        ```

        Closure that returns `MiddlewareInterface`:

        ```php
        static function (): MiddlewareInterface {
            return new TestMiddleware();
        }
        ```

        Action in controller:

        ```php
        [App\Backend\UserController::class, 'index']
        ```

        ## Related links

        - [Array definition syntax](https://github.com/yiisoft/definitions#arraydefinition)
        - [Callable PHP documentation](https://www.php.net/manual/language.types.callable.php)
        SOLUTION;

        return implode("\n\n", $solution);
    }

    private function generateSuggestion(): ?string
    {
        if (DefinitionHelper::isControllerWithNonExistAction($this->definition)) {
            return <<<SOLUTION
            Class `{$this->definition[0]}` exists, but does not contain method `{$this->definition[1]}()`.

            Try adding `{$this->definition[1]}()` action to `{$this->definition[0]}` controller:

            ```php
            public function {$this->definition[1]}(): ResponseInterface
            {
                // TODO: Implement your action
            }
            ```
            SOLUTION;
        }

        if (DefinitionHelper::isNotMiddlewareClassName($this->definition)) {
            return sprintf(
                'Class `%s` exists, but does not implement `%s`.',
                $this->definition,
                MiddlewareInterface::class
            );
        }

        if (DefinitionHelper::isStringNotClassName($this->definition)) {
            return sprintf(
                'Class `%s` not found. It may be needed to install a package with this middleware.',
                $this->definition
            );
        }

        if (is_array($this->definition)) {
            try {
                DefinitionValidator::validateArrayDefinition($this->definition);
            } catch (InvalidConfigException $e) {
                return <<<SOLUTION
                You may have an error in array definition. Array definition validation result:

                ```
                {$e->getMessage()}
                ```
                SOLUTION;
            }

            /** @psalm-suppress MixedArgument In valid array definition element "class" always is string */
            return sprintf(
                'Array definition is valid, class `%s` exists, but does not implement `%s`.',
                $this->definition['class'],
                MiddlewareInterface::class
            );
        }

        return null;
    }
}
