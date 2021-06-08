<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * A pipeline of PSR-15 middlewares to be used with {@see MiddlewareDispatcher}.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 */
interface MiddlewarePipelineInterface extends RequestHandlerInterface
{
    /**
     * Clears the middleware pipeline and fallback request handler.
     */
    public function reset(): void;
}
