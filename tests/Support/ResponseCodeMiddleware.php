<?php
declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\ActionParametersCollector\ActionParametersCollectorInterface;

final class ResponseCodeMiddleware implements MiddlewareInterface
{
    private ActionParametersCollectorInterface $actionParametersCollector;
    private int $statusCode;

    public function __construct(int $statusCode, ActionParametersCollectorInterface $actionParametersCollector)
    {
        $this->actionParametersCollector = $actionParametersCollector;
        $this->statusCode = $statusCode;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->actionParametersCollector->addParameter(['statusCode' => $this->statusCode]);
        return $handler->handle($request);
    }
}
