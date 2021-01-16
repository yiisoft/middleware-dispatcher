<?php
declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ActionParametersCollector;

interface ActionParametersCollectorInterface
{
    public function addParameter(array $parameter): void;

    public function hasParameter($parameter): bool;

    public function removeParameter($parameter): void;

    public function getParameters(): array;
}
