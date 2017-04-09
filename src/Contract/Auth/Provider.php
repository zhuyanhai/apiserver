<?php
/**
 * 认证服务提供者接口
 */

namespace Zyh\ApiServer\Contract\Auth;

use Illuminate\Http\Request;
use Zyh\ApiServer\Routing\Route;

interface Provider
{
    /**
     * 请求身份验证并且返回身份验证用户实例
     *
     * @param \Illuminate\Http\Request $request
     * @param \Zyh\ApiServer\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route);
}
