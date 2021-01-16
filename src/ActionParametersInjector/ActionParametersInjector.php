<?php
declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ActionParametersInjector;

class ActionParametersInjector implements ActionParametersInjectorInterface
{
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function addParameter($parameter): void
    {
        $this->params[] = $parameter;
    }

    public function hasParameter($parameter): bool
    {
        return in_array($this->params, $parameter, true);
    }

    public function removeParameter($parameter): void
    {
        unset($this->params[array_search($parameter, $this->params, true)]);
    }

    public function getParameters(): array
    {
        return $this->params;
    }
}
