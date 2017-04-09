<?php

namespace Zyh\ApiServer\Contract\Routing;

use Illuminate\Http\Request;

interface Adapter
{
    /**
     * 调度一个请求
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $version
     *
     * @return mixed
     */
    public function dispatch(Request $request, $version);

    /**
     * 从路由中获取URI、方法、动作
     *
     * @param mixed                    $route
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getRouteProperties($route, Request $request);

    /**
     * 添加一个路由到路由集合
     *
     * @param array  $methods
     * @param array  $versions
     * @param string $uri
     * @param mixed  $action
     *
     * @return void
     */
    public function addRoute(array $methods, array $versions, $uri, $action);

    /**
     * 获取所有路由 或者 仅获取指定版本的路由
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getRoutes($version = null);

    /**
     * Get a normalized iterable set of routes. Top level key must be a version with each
     * version containing iterable routes that can be consumed by the adapter.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getIterableRoutes($version = null);

    /**
     * Set the routes on the adapter.
     * 设置路由
     *
     * @param array $routes
     *
     * @return void
     */
    public function setRoutes(array $routes);

    /**
     * Prepare a route for serialization.
     *
     * @param mixed $route
     *
     * @return mixed
     */
    public function prepareRouteForSerialization($route);
}
