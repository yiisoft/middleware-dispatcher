<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Attribute\Middleware;

final class SetHeaderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $header,
        private string $value,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader($this->header, $this->value);
    }
}
