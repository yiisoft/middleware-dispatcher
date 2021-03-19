<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A pipeline of PSR-15 middlewares to be used with {@see MiddlewareDispatcher}.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 */
interface MiddlewarePipelineInterface extends RequestHandlerInterface
{
    /**
     * Builds a middleware pipeline from an array of middleware instances and a fallback request handler.
     * If the middlewares array is empty, only the fallback handler will be used.
     *
     * This method MUST be implemented in such a way as to retain the immutability of the pipeline,
     * and MUST return an instance that has the new pipeline.
     *
     * @param MiddlewareInterface[] $middlewares Middlewares being composed to pipeline. Can be empty.
     * @param RequestHandlerInterface $fallbackHandler Fallback request handler.
     *
     * @return self
     */
    public function build(array $middlewares, RequestHandlerInterface $fallbackHandler): self;
}
