<?php

if (! function_exists('version')) {
    /**
     * 设置版本号到api的url中
     *
     * @param string $version 例如 v1
     *
     * @return \Zyh\ApiServer\Routing\UrlGenerator
     */
    function version($version)
    {
        return app('api.url')->version($version);
    }
}
