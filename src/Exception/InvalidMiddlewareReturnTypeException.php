<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;
use Yiisoft\Middleware\Dispatcher\Helper\DefinitionHelper;
use Yiisoft\Middleware\Dispatcher\Helper\ResponseHelper;

final class InvalidMiddlewareReturnTypeException extends AbstractInvalidMiddlewareException
{
    public function __construct(
        mixed $definition,
        private readonly mixed $result,
        ?Throwable $previous = null,
    ) {
        $this->definitionString = DefinitionHelper::convertDefinitionToString($definition);

        parent::__construct(
            $definition,
            sprintf(
                'Middleware %s must return an instance of `%s` or `%s`, %s returned.',
                $this->definitionString,
                MiddlewareInterface::class,
                ResponseInterface::class,
                ResponseHelper::convertToString($this->result)
            ),
            $previous,
        );
    }

    public function getName(): string
    {
        return sprintf('Invalid middleware result type %s', get_debug_type($this->result));
    }
}
