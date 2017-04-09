<?php

namespace Zyh\ApiServer\Contract\Http\RateLimit;

use Zyh\ApiServer\Http\Request;
use Illuminate\Container\Container;

interface HasRateLimiter
{
    /**
     * 获取一个可用的阀值器
     *
     * @param \Illuminate\Container\Container $app
     * @param \Zyh\ApiServer\Http\Request         $request
     *
     * @return string
     */
    public function getRateLimiter(Container $app, Request $request);
}
