<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\ActionParametersCollector;

class ActionParametersCollector implements ActionParametersCollectorInterface
{
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function addParameter(array $parameter): void
    {
        foreach ($parameter as $key => $value) {
            if (is_int($key)) {
                $this->params[] = $parameter;
            } elseif (is_string($key)) {
                $this->params[$key] = $value;
            }
        }
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
