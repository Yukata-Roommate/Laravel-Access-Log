<?php

namespace YukataRm\Laravel\AccessLog\Provider;

use Illuminate\Support\ServiceProvider as Provider;

use YukataRm\Laravel\AccessLog\Middleware\LoggingHttpAccess;

use Illuminate\Contracts\Http\Kernel;

/**
 * ServiceProvider
 * 
 * @package YukataRm\Laravel\AccessLog\Provider
 */
class ServiceProvider extends Provider
{
    /**
     * Register Middleware
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(LoggingHttpAccess::class);
    }

    /**
     * Push Middleware
     * Publish config
     * 
     * @return void
     */
    public function boot(): void
    {
        $kernel = app(Kernel::class);
        $kernel->pushMiddleware(LoggingHttpAccess::class);

        $this->publishes([
            $this->publicationsPath("config") => config_path("yukata-roommate"),
        ], "yukata-roommate");
    }

    /**
     * get path to publications
     * 
     * @param string|array<string> $path
     * @return string
     */
    private function publicationsPath(string|array $path): string
    {
        if (is_array($path)) $path = implode(DIRECTORY_SEPARATOR, $path);

        return __DIR__ . DIRECTORY_SEPARATOR . "publications" . DIRECTORY_SEPARATOR . $path;
    }
}
