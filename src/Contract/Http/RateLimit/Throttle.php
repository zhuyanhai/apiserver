<?php

namespace Zyh\ApiServer\Contract\Http\RateLimit;

use Illuminate\Container\Container;

interface Throttle
{
    /**
     * 根据给定的条件尝试匹配阀值
     *
     * @param \Illuminate\Container\Container $container
     *
     * @return bool
     */
    public function match(Container $container);

    /**
     * 获取请求限制的过期时间 分钟
     *
     * @return int
     */
    public function getExpires();

    /**
     * 获取请求限制阀值
     *
     * @return int
     */
    public function getLimit();
}
