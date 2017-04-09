<?php
/**
 * 调度器类
 */

namespace Zyh\ApiServer;

use Zyh\ApiServer\Auth\Auth;
use Zyh\ApiServer\Routing\Router;
use Illuminate\Container\Container;
use Zyh\ApiServer\Http\InternalRequest;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Zyh\ApiServer\Exception\InternalHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Dispatcher
{
    /**
     * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 路由器实例
     *
     * @var \Zyh\ApiServer\Routing\Router
     */
    protected $router;

    /**
     * Auth 实例.
     *
     * @var \Zyh\ApiServer\Auth\Auth
     */
    protected $auth;

    /**
     * 内部请求堆栈
     *
     * @var array
     */
    protected $requestStack = [];

    /**
     * 内部路由堆栈
     *
     * @var array
     */
    protected $routeStack = [];

    /**
     * 关于请求报头中的版本号
     *
     * @var string
     */
    protected $version;

    /**
     * 请求头的数组
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 请求的 cookies
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * 请求参数
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * 请求的原始内容
     *
     * @var string
     */
    protected $content;

    /**
     * 上传文件的请求
     *
     * @var array
     */
    protected $uploads = [];

    /**
     * 请求使用的域名
     *
     * @var string
     */
    protected $domain;

    /**
     * 表示返回的响应对象是否是原始的响应对象
     *
     * @var bool
     */
    protected $raw = false;

    /**
     * 表示身份验证是否依然存在
     *
     * @var bool
     */
    protected $persistAuthentication = true;

    /**
     * API 子类型.
     *
     * @var string
     */
    protected $subtype;

    /**
     * API 标准树.
     *
     * @var string
     */
    protected $standardsTree;

    /**
     * API 前缀.
     *
     * @var string
     */
    protected $prefix;

    /**
     * 默认版本号
     *
     * @var string
     */
    protected $defaultVersion;

    /**
     * 默认域名
     *
     * @var string
     */
    protected $defaultDomain;

    /**
     * 默认格式
     *
     * @var string
     */
    protected $defaultFormat;

    /**
     * 创建一个调度器实例
     *
     * @param \Illuminate\Container\Container   $container
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Zyh\ApiServer\Routing\Router         $router
     * @param \Zyh\ApiServer\Auth\Auth              $auth
     *
     * @return void
     */
    public function __construct(Container $container, Filesystem $files, Router $router, Auth $auth)
    {
        $this->container = $container;
        $this->files      = $files;
        $this->router     = $router;
        $this->auth       = $auth;

        //设置请求堆栈
        $this->setupRequestStack();
    }

    /**
     * 根据初始请求设置请求堆栈
     * @return void
     */
    protected function setupRequestStack()
    {
        $this->requestStack[] = $this->container['request'];
    }

    /**
     * 上传的附加文件
     *
     * @param array $files
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function attach(array $files)
    {
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $file = new UploadedFile($file['path'], basename($file['path']), $file['mime'], $file['size']);
            } elseif (is_string($file)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                $file = new UploadedFile($file, basename($file), finfo_file($finfo, $file), $this->files->size($file));
            } elseif (! $file instanceof UploadedFile) {
                continue;
            }

            $this->uploads[$key] = $file;
        }

        return $this;
    }

    /**
     * 内部请求将用户定义为已做过身份验证
     *
     * @param mixed $user
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function be($user)
    {
        $this->auth->setUser($user);

        return $this;
    }

    /**
     * 设置返回JSON格式的内容
     *
     * @param string|array $content
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function json($content)
    {
        if (is_array($content)) {
            $content = json_encode($content);
        }

        $this->content = $content;

        return $this->header('Content-Type', 'application/json');
    }

    /**
     * 为请求设置一个域名
     *
     * @param string $domain
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function on($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 设置返回一个原始响应对象
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function raw()
    {
        $this->raw = true;

        return $this;
    }

    /**
     * 给指定用户的单个请求进行验证
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function once()
    {
        $this->persistAuthentication = false;

        return $this;
    }

    /**
     * 为下一个请求设置关于API的版本号
     *
     * @param string $version
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function version($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * 设置下一个API请求发送的参数
     *
     * @param string|array $parameters
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function with($parameters)
    {
        $this->parameters = array_merge($this->parameters, is_array($parameters) ? $parameters : func_get_args());

        return $this;
    }

    /**
     * 设置下一个API请求发送的头
     *
     * @param string $key
     * @param string $value
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function header($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * 设置下一个API请求发送的cookie
     *
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     *
     * @return \Zyh\ApiServer\Dispatcher
     */
    public function cookie(Cookie $cookie)
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    /**
     * 执行API GET 请求
     *
     * @param string       $uri
     * @param string|array $parameters
     *
     * @return mixed
     */
    public function get($uri, $parameters = [])
    {
        return $this->queueRequest('get', $uri, $parameters);
    }

    /**
     * P执行API POST 请求
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function post($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('post', $uri, $parameters, $content);
    }

    /**
     * 执行API PUT 请求
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function put($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('put', $uri, $parameters, $content);
    }

    /**
     * 执行API PATCH 请求
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function patch($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('patch', $uri, $parameters, $content);
    }

    /**
     * 执行API DELETE 请求
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function delete($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('delete', $uri, $parameters, $content);
    }

    /**
     * 队列请求，排队分配一个新的请求
     *
     * @param string       $verb
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    protected function queueRequest($verb, $uri, $parameters, $content = '')
    {
        if (! empty($content)) {
            $this->content = $content;
        }

        // Sometimes after setting the initial request another request might be made prior to
        // internally dispatching an API request. We need to capture this request as well
        // and add it to the request stack as it has become the new parent request to
        // this internal request. This will generally occur during tests when
        // using the crawler to navigate pages that also make internal
        // requests.
        if (end($this->requestStack) != $this->container['request']) {
            $this->requestStack[] = $this->container['request'];
        }

        $this->requestStack[] = $request = $this->createRequest($verb, $uri, $parameters);

        return $this->dispatch($request);
    }

    /**
     * Create a new internal request from an HTTP verb and URI.
     *
     * @param string       $verb
     * @param string       $uri
     * @param string|array $parameters
     *
     * @return \Zyh\ApiServer\Http\InternalRequest
     */
    protected function createRequest($verb, $uri, $parameters)
    {
        $parameters = array_merge($this->parameters, (array) $parameters);

        $uri = $this->addPrefixToUri($uri);

        // If the URI does not have a scheme then we can assume that there it is not an
        // absolute URI, in this case we'll prefix the root requests path to the URI.
        $rootUrl = $this->getRootRequest()->root();
        if ((! parse_url($uri, PHP_URL_SCHEME)) && parse_url($rootUrl) !== false) {
            $uri = rtrim($rootUrl, '/').'/'.ltrim($uri, '/');
        }

        $request = InternalRequest::create(
            $uri,
            $verb,
            $parameters,
            $this->cookies,
            $this->uploads,
            $this->container['request']->server->all(),
            $this->content
        );

        $request->headers->set('host', $this->getDomain());

        foreach ($this->headers as $header => $value) {
            $request->headers->set($header, $value);
        }

        $request->headers->set('accept', $this->getAcceptHeader());

        return $request;
    }

    /**
     * 为URI添加前缀
     *
     * @param string $uri
     *
     * @return string
     */
    protected function addPrefixToUri($uri)
    {
        if (! isset($this->prefix)) {
            return $uri;
        }

        $uri = trim($uri, '/');

        if (starts_with($uri, $this->prefix)) {
            return $uri;
        }

        return rtrim('/'.trim($this->prefix, '/').'/'.$uri, '/');
    }

    /**
     * 构建 Accept（请求报头） 头
     *
     * @return string
     */
    protected function getAcceptHeader()
    {
        return sprintf('application/%s.%s.%s+%s', $this->getStandardsTree(), $this->getSubtype(), $this->getVersion(), $this->getFormat());
    }

    /**
     * 尝试调度一个内部请求
     *
     * @param \Zyh\ApiServer\Http\InternalRequest $request
     *
     * @throws \Exception|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
     *
     * @return mixed
     */
    protected function dispatch(InternalRequest $request)
    {
        $this->routeStack[] = $this->router->getCurrentRoute();

        $this->clearCachedFacadeInstance();

        try {
            $this->container->instance('request', $request);

            $response = $this->router->dispatch($request);

            if (! $response->isSuccessful() && ! $response->isRedirection()) {
                throw new InternalHttpException($response);
            } elseif (! $this->raw) {
                $response = $response->getOriginalContent();
            }
        } catch (HttpExceptionInterface $exception) {
            $this->refreshRequestStack();

            throw $exception;
        }

        $this->refreshRequestStack();

        return $response;
    }

    /**
     * 刷新请求堆栈
     *
     * This is done by resetting the authentication, popping
     * the last request from the stack, replacing the input,
     * and resetting the version and parameters.
     *
     * @return void
     */
    protected function refreshRequestStack()
    {
        if (! $this->persistAuthentication) {
            $this->auth->setUser(null);

            $this->persistAuthentication = true;
        }

        if ($route = array_pop($this->routeStack)) {
            $this->router->setCurrentRoute($route);
        }

        $this->replaceRequestInstance();

        $this->clearCachedFacadeInstance();

        $this->raw = false;

        $this->version = $this->domain = $this->content = null;

        $this->parameters = $this->uploads = [];
    }

    /**
     * 使用前一个请求实例替换当前的请求实例
     *
     * @return void
     */
    protected function replaceRequestInstance()
    {
        array_pop($this->requestStack);

        $this->container->instance('request', end($this->requestStack));
    }

    /**
     * 清除门面实例缓存
     *
     * @return void
     */
    protected function clearCachedFacadeInstance()
    {
        // Facades cache the resolved instance so we need to clear out the
        // request instance that may have been cached. Otherwise we'll
        // may get unexpected results.
        RequestFacade::clearResolvedInstance('request');
    }

    /**
     * 获取第一个请求实例
     *
     * @return \Illuminate\Http\Request
     */
    protected function getRootRequest()
    {
        return reset($this->requestStack);
    }

    /**
     * 获取域名
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain ?: $this->defaultDomain;
    }

    /**
     * 获取版本号
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version ?: $this->defaultVersion;
    }

    /**
     * 获取格式类型
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->defaultFormat;
    }

    /**
     * 获取子类型
     *
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * 设置子类型
     *
     * @param string $subtype
     *
     * @return void
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }

    /**
     * 获取标准树
     *
     * @return string
     */
    public function getStandardsTree()
    {
        return $this->standardsTree;
    }

    /**
     * 设置标准树
     *
     * @param string $standardsTree
     *
     * @return void
     */
    public function setStandardsTree($standardsTree)
    {
        $this->standardsTree = $standardsTree;
    }

    /**
     * 设置前缀
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * 设置默认版本号
     *
     * @param string $version
     *
     * @return void
     */
    public function setDefaultVersion($version)
    {
        $this->defaultVersion = $version;
    }

    /**
     * 设置默认域名
     *
     * @param string $domain
     *
     * @return void
     */
    public function setDefaultDomain($domain)
    {
        $this->defaultDomain = $domain;
    }

    /**
     * 设置默认格式
     *
     * @param string $format
     *
     * @return void
     */
    public function setDefaultFormat($format)
    {
        $this->defaultFormat = $format;
    }
}
