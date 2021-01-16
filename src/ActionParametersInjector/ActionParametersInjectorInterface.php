<?php
declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ActionParametersInjector;

interface ActionParametersInjectorInterface
{
    public function addParameter($parameter): void;

    public function hasParameter($parameter): bool;

    public function removeParameter($parameter): void;

    public function getParameters(): array;
}
