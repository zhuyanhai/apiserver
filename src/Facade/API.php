<?php

namespace Zyh\ApiServer\Facade;

use Zyh\ApiServer\Http\InternalRequest;
use Illuminate\Support\Facades\Facade;

class API extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'api.dispatcher';
    }

    /**
     * Bind an exception handler.
     *
     * @param callable $callback
     *
     * @return void
     */
    public static function error(callable $callback)
    {
        return static::$app['api.exception']->register($callback);
    }

    /**
     * Get the authenticator.
     *
     * @return \Zyh\ApiServer\Auth\Auth
     */
    public static function auth()
    {
        return static::$app['api.auth'];
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public static function user()
    {
        return static::$app['api.auth']->user();
    }

    /**
     * Determine if a request is internal.
     *
     * @return bool
     */
    public static function internal()
    {
        return static::$app['api.router']->getCurrentRequest() instanceof InternalRequest;
    }

    /**
     * Get the API router instance.
     *
     * @return \Zyh\ApiServer\Routing\Router
     */
    public static function router()
    {
        return static::$app['api.router'];
    }
}
