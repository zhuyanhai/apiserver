<?php

namespace Zyh\ApiServer\Http\Middleware;

use Closure;
use Zyh\ApiServer\Routing\Router;
use Zyh\ApiServer\Auth\Auth as Authentication;

class Auth
{
    /**
     * Router instance.
     *
     * @var \Zyh\ApiServer\Routing\Router
     */
    protected $router;

    /**
     * Authenticator instance.
     *
     * @var \Zyh\ApiServer\Auth\Auth
     */
    protected $auth;

    /**
     * Create a new auth middleware instance.
     *
     * @param \Zyh\ApiServer\Routing\Router $router
     * @param \Zyh\ApiServer\Auth\Auth      $auth
     *
     * @return void
     */
    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    /**
     * Perform authentication before a request is executed.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if (! $this->auth->check(false)) {
            $this->auth->authenticate($route->getAuthenticationProviders());
        }

        return $next($request);
    }
}
