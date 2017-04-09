<?php
/**
 * url 生成器
 */

namespace Zyh\ApiServer\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator as IlluminateUrlGenerator;

class UrlGenerator extends IlluminateUrlGenerator
{
    /**
     * 理由集合数组
     *
     * @var array
     */
    protected $collections;

    /**
     * 创建一个 URL 生成器实例
     *
     * @param \Zyh\ApiServer\Http\Request $request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        //IlluminateUrlGenerator 类中的方法
        //设置当前的请求实例
        $this->setRequest($request);
    }

    /**
     * 设置路由器（使用那个版本的），根据版本号定义路由
     * 请求报头中的版本号
     *
     * @param string $version
     *
     * @return \Zyh\ApiServer\Routing\UrlGenerator
     */
    public function version($version)
    {
        $this->routes = $this->collections[$version];

        return $this;
    }

    /**
     * 设置路由实例集合
     *
     * @param array $collections
     */
    public function setRouteCollections(array $collections)
    {
        $this->collections = $collections;
    }
}
