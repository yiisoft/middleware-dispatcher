<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Throwable;
use Yiisoft\Middleware\Dispatcher\Exception\AbstractInvalidMiddlewareException;
use Yiisoft\Middleware\Dispatcher\Helper\DefinitionHelper;

final class InvalidMiddlewareDefinitionException extends AbstractInvalidMiddlewareException
{
    public function __construct(
        mixed $definition,
        ?Throwable $previous = null,
    ) {
        $this->definitionString = DefinitionHelper::convertDefinitionToString($definition);

        parent::__construct(
            $definition,
            sprintf(
                'Parameter should be either PSR middleware class name or a callable. Got %s.',
                $this->definitionString
            ),
            $previous,
        );
    }

    public function getName(): string
    {
        return 'Invalid middleware definition';
    }
}
