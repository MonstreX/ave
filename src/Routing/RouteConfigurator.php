<?php

namespace Monstrex\Ave\Routing;

use Illuminate\Routing\Router;

class RouteConfigurator
{
    public function __construct(
        protected Router $router,
        protected array $options = [],
    ) {
    }

    public static function make(Router $router, array $options = []): self
    {
        return new self($router, $options);
    }

    public static function forAdmin(Router $router, string $prefix, array $middleware): self
    {
        return self::make($router, [
            'prefix' => $prefix,
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param  callable(\Illuminate\Routing\Router): void  $callback
     */
    public function register(callable $callback): void
    {
        $this->router->group($this->options, function (Router $router) use ($callback) {
            $callback($router);
        });
    }
}
