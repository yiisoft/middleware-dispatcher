<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ServerRequestInterface;

final class SimpleParametersResolver implements ParametersResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        return [];
    }
}
