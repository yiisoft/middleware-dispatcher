<?php

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;

final class SimpleParametersResolver implements ParametersResolverInterface
{

    /**
     * @inheritDoc
     */
    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        return ['test' => 'yii'];
    }
}
