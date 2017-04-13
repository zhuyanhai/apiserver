<?php

namespace Zyh\ApiServer\Http\Middleware;

use Closure;
use Zyh\ApiServer\Http\Response;
use Zyh\ApiServer\Routing\Router;
use Zyh\ApiServer\Http\InternalRequest;
use Zyh\ApiServer\Http\RateLimit\Handler;
use Zyh\ApiServer\Exception\RateLimitExceededException;

class RateLimit
{
    /**
     * Router instance.
     *
     * @var \Zyh\ApiServer\Routing\Router
     */
    protected $router;

    /**
     * Rate limit handler instance.
     *
     * @var \Zyh\ApiServer\Http\RateLimit\Handler
     */
    protected $handler;

    /**
     * Create a new rate limit middleware instance.
     *
     * @param \Zyh\ApiServer\Routing\Router         $router
     * @param \Zyh\ApiServer\Http\RateLimit\Handler $handler
     *
     * @return void
     */
    public function __construct(Router $router, Handler $handler)
    {
        $this->router = $router;
        $this->handler = $handler;
    }

    /**
     * Perform rate limiting before a request is executed.
     *
     * @param \Zyh\ApiServer\Http\Request $request
     * @param \Closure                $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request instanceof InternalRequest) {
            return $next($request);
        }

        $route = $this->router->getCurrentRoute();

        if ($route->hasThrottle()) {
            $this->handler->setThrottle($route->getThrottle());
        }

        $this->handler->rateLimitRequest($request, $route->getRateLimit(), $route->getRateLimitExpiration());

        if ($this->handler->exceededRateLimit()) {
            throw new RateLimitExceededException('You have exceeded your rate limit.', null, $this->getHeaders());
        }

        $response = $next($request);

        if ($this->handler->requestWasRateLimited()) {
            return $this->responseWithHeaders($response);
        }

        return $response;
    }

    /**
     * Send the response with the rate limit headers.
     *
     * @param \Zyh\ApiServer\Http\Response $response
     *
     * @return \Zyh\ApiServer\Http\Response
     */
    protected function responseWithHeaders($response)
    {
        foreach ($this->getHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Get the headers for the response.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return [
            'X-RateLimit-Limit' => $this->handler->getThrottleLimit(),
            'X-RateLimit-Remaining' => $this->handler->getRemainingLimit(),
            'X-RateLimit-Reset' => $this->handler->getRateLimitReset(),
        ];
    }
}
