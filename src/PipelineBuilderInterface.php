<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * Build RequestHandler from Middleware definitions list.
 */
interface PipelineBuilderInterface
{
    /**
     * Just run result with your ServerRequest instance.
     */
    public function buildPipeline(iterable $middlewares, RequestHandlerInterface $fallbackHandler): RequestHandlerInterface;
}
