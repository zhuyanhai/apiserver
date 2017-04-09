<?php

namespace Zyh\ApiServer\Contract\Http;

use Illuminate\Http\Request as IlluminateRequest;

interface Validator
{
    /**
     * 验证一个请求
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validate(IlluminateRequest $request);
}
