<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support\ParametersResolver;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;

use function array_key_exists;

final class NameParametersResolver implements ParametersResolverInterface
{
    public function __construct(private array $data) {}

    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        $result = [];
        foreach ($parameters as $name => $_parameter) {
            if (array_key_exists($name, $this->data)) {
                $result[$name] = $this->data[$name];
            }
        }

        return $result;
    }
}
