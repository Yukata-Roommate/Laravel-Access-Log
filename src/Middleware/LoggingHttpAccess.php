<?php

namespace YukataRm\Laravel\AccessLog\Middleware;

use YukataRm\Laravel\SimpleTimer\Interface\TimerInterface;
use YukataRm\Laravel\SimpleTimer\Facade\Timer as TimerFacade;
use YukataRm\Laravel\SimpleLogger\Interface\LoggerInterface;
use YukataRm\Laravel\SimpleLogger\Facade\Logger as LoggerFacade;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Logging HTTP Access Middleware
 * 
 * @package YukataRm\Laravel\AccessLog\Middleware
 */
class LoggingHttpAccess
{
    /**
     * Timer instance
     *
     * @var \YukataRm\Laravel\SimpleTimer\Interface\TimerInterface
     */
    public TimerInterface $timer;

    /**
     * handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     */
    public function handle(Request $request, \Closure $next): SymfonyResponse
    {
        $this->timer = TimerFacade::start();

        return $next($request);
    }

    /**
     * terminate an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param IlluminateResponse|RedirectResponse|JsonResponse $response
     * @return void
     */
    public function terminate(Request $request, IlluminateResponse|RedirectResponse|JsonResponse $response): void
    {
        if (!$this->isEnable()) return;

        $this->timer->stop();

        $contents = $this->getContents($request, $response);

        $logger = $this->getLogger();

        $logger->add($contents);

        $logger->logging();
    }

    /*----------------------------------------*
     * Logging
     *----------------------------------------*/

    /**
     * whether to enable logging
     * 
     * @return bool
     */
    private function isEnable(): bool
    {
        if (!$this->configEnable()) return false;

        $ignoreRegexes = $this->configIgnoreUri();

        foreach ($ignoreRegexes as $regex) {
            if (!preg_match($regex, request()->getRequestUri())) continue;

            return false;
        }

        return true;
    }

    /**
     * get Logger instance
     * 
     * @return \YukataRm\Laravel\SimpleLogger\Interface\LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        $logger = LoggerFacade::info();

        $logger->setDirectory($this->configDirectory());

        $logger->setFormat("%message%");

        return $logger;
    }

    /*----------------------------------------*
     * Contents
     *----------------------------------------*/

    /**
     * get contents
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse $response
     * @return array
     */
    private function getContents(Request $request, IlluminateResponse|RedirectResponse|JsonResponse $response): array
    {
        return [
            "timestamp"         => $this->timestamp(),
            "execution_time"    => $this->executionTime(),
            "memory_peak_usage" => $this->memoryPeakUsage(),

            "request" => [
                "url"         => $this->requestUrl($request),
                "http_method" => $this->requestHttpMethod($request),
                "user_agent"  => $this->requestUserAgent($request),
                "ip_address"  => $this->requestIpAddress($request),
                "body"        => $this->requestBody($request),
            ],

            "response" => [
                "status"      => $this->responseStatus($response),
                "status_text" => $this->responseStatusText($response),
            ],
        ];
    }

    /**
     * get timestamp
     * 
     * @return string
     */
    private function timestamp(): string
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * get execution time
     * 
     * @return string
     */
    private function executionTime(): string
    {
        return $this->configExecutionTime() ? $this->timer->elapsedMilliseconds() : "";
    }

    /**
     * get memory peak usage
     * 
     * @return string
     */
    private function memoryPeakUsage(): string
    {
        return $this->configMemoryPeakUsage() ? memory_get_peak_usage() : "";
    }

    /**
     * get request url
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function requestUrl(Request $request): string
    {
        return $this->configRequestUrl() ? e($request->getRequestUri()) : "";
    }

    /**
     * get request http method
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function requestHttpMethod(Request $request): string
    {
        return $this->configRequestHttpMethod() ? $request->method() : "";
    }

    /**
     * get request user agent
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function requestUserAgent(Request $request): string
    {
        return $this->configRequestUserAgent() ? $request->userAgent() : "";
    }

    /**
     * get request ip address
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function requestIpAddress(Request $request): string
    {
        return $this->configRequestIpAddress() ? $request->ip() : "";
    }

    /**
     * get request body
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    private function requestBody(Request $request): array
    {
        return $this->configRequestBody() ? $this->masking($request->all()) : [];
    }

    /**
     * get response status
     * 
     * @param \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse $response
     * @return string
     */
    private function responseStatus(IlluminateResponse|RedirectResponse|JsonResponse $response): string
    {
        return $this->configResponseStatus() ? $response->status() : "";
    }

    /**
     * get response status text
     * 
     * @param \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse $response
     * @return string
     */
    private function responseStatusText(IlluminateResponse|RedirectResponse|JsonResponse $response): string
    {
        return $this->configResponseStatusText() ? $response->statusText() : "";
    }

    /*----------------------------------------*
     * Masking
     *----------------------------------------*/

    /**
     * mask parameters in array
     * 
     * @param array $contents
     * @return array
     */
    private function masking(array $contents): array
    {
        $maskingParameters = $this->configMaskingParameters();

        foreach ($maskingParameters as $key) {
            if (!array_key_exists($key, $contents)) continue;

            $contents[$key] = $this->configMaskingText();
        }

        return $contents;
    }

    /*----------------------------------------*
     * Config
     *----------------------------------------*/

    /**
     * get config or default
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config(string $key, mixed $default): mixed
    {
        return config("yukata-roommate.log.access.{$key}", $default);
    }

    /**
     * get config enable
     * 
     * @return bool
     */
    protected function configEnable(): bool
    {
        return $this->config("enable", false);
    }

    /**
     * get config directory
     * 
     * @return string
     */
    protected function configDirectory(): string
    {
        return $this->config("directory", "access");
    }

    /**
     * get config ignore uri
     * 
     * @return array<string>
     */
    protected function configIgnoreUri(): array
    {
        return $this->config("ignore_uri", []);
    }

    /**
     * get config execution time
     * 
     * @return bool
     */
    protected function configExecutionTime(): bool
    {
        return $this->config("execution_time", false);
    }

    /**
     * get config memory peak usage
     * 
     * @return bool
     */
    protected function configMemoryPeakUsage(): bool
    {
        return $this->config("memory_peak_usage", false);
    }

    /**
     * get config request url
     * 
     * @return bool
     */
    protected function configRequestUrl(): bool
    {
        return $this->config("request_url", false);
    }

    /**
     * get config request http method
     * 
     * @return bool
     */
    protected function configRequestHttpMethod(): bool
    {
        return $this->config("request_http_method", false);
    }

    /**
     * get config request user agent
     * 
     * @return bool
     */
    protected function configRequestUserAgent(): bool
    {
        return $this->config("request_user_agent", false);
    }

    /**
     * get config request ip address
     * 
     * @return bool
     */
    protected function configRequestIpAddress(): bool
    {
        return $this->config("request_ip_address", false);
    }

    /**
     * get config request body
     * 
     * @return bool
     */
    protected function configRequestBody(): bool
    {
        return $this->config("request_body", false);
    }

    /**
     * get config response status
     * 
     * @return bool
     */
    protected function configResponseStatus(): bool
    {
        return $this->config("response_status", false);
    }

    /**
     * get config response status text
     * 
     * @return bool
     */
    protected function configResponseStatusText(): bool
    {
        return $this->config("response_status_text", false);
    }

    /**
     * get config masking text
     * 
     * @return string
     */
    protected function configMaskingText(): string
    {
        return $this->config("masking_text", "********");
    }

    /**
     * get config masking parameters
     * 
     * @return array<string>
     */
    protected function configMaskingParameters(): array
    {
        return $this->config("masking_parameters", []);
    }
}
