<?php

namespace YukataRm\Laravel\AccessLog\Provider;

use Illuminate\Support\ServiceProvider;

use YukataRm\Laravel\AccessLog\Middleware\LoggingHttpAccess;

use Illuminate\Contracts\Http\Kernel;

/**
 * Middleware Service Provider
 * 
 * @package YukataRm\Laravel\AccessLog\Provider
 */
class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * register Middleware
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(LoggingHttpAccess::class);
    }

    /**
     * push Middleware
     * 
     * @return void
     */
    public function boot(): void
    {
        $kernel = app(Kernel::class);
        $kernel->pushMiddleware(LoggingHttpAccess::class);
    }
}
