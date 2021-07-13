<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BindRequestAttributesController
{
    public function index(ServerRequestInterface $request, RequestHandlerInterface $requestHandler, string $handler): ResponseInterface
    {
        $response = $requestHandler->handle($request);
        return $response->withHeader('handler', $handler);
    }
}
