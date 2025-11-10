<?php

namespace Monstrex\Ave\Routing;

use Illuminate\Routing\Router;

class RouteConfigurator
{
    public function __construct(
        protected Router ,
        protected array  = [],
    ) {}

    public static function make(Router , array  = []): self
    {
        return new self(, );
    }

    public static function forAdmin(Router , string , array ): self
    {
        return self::make(, [
            'prefix' => ,
            'middleware' => ,
        ]);
    }

    /**
     * @param  callable(\Illuminate\Routing\Router): void  
     */
    public function register(callable ): void
    {
        ->router->group(->options, function (Router ) use () {
            ();
        });
    }
}
