<?php
/**
 * 认证类
 */

namespace Zyh\ApiServer\Auth;

use Exception;
use Zyh\ApiServer\Routing\Router;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Auth
{
    /**
     * 路由实例
     *
     * @var \Zyh\ApiServer\Routing\Router
     */
    protected $router;

    /**
     * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * 可用的身份验证服务提供者数组
     *
     * @var array
     */
    protected $providers;

    /**
     * 身份验证的服务提供者
     *
     * @var \Zyh\ApiServer\Contract\Auth\Provider
     */
    protected $providerUsed;

    /**
     * 身份验证用户实例
     *
     * @var \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * 创建一个认证实例
     *
     * @param \Zyh\ApiServer\Routing\Router       $router
     * @param \Illuminate\Container\Container $container
     * @param array                           $providers
     *
     * @return void
     */
    public function __construct(Router $router, Container $container, array $providers)
    {
        $this->router    = $router;
        $this->container = $container;
        $this->providers = $providers;
    }

    /**
     * 当前请求进行身份验证
     *
     * @param array $providers
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return mixed
     */
    public function authenticate(array $providers = [])
    {
        //异常堆栈
        $exceptionStack = [];

        //使用每一个注册的（且可用的）身份验证提供者循环验证
        foreach ($this->filterProviders($providers) as $provider) {
            try {
                $user = $provider->authenticate($this->router->getCurrentRequest(), $this->router->getCurrentRoute());

                //验证通过的服务提供者
                $this->providerUsed = $provider;

                return $this->user = $user;
            } catch (UnauthorizedHttpException $exception) {
                $exceptionStack[] = $exception;
            } catch (BadRequestHttpException $exception) {
                // We won't add this exception to the stack as it's thrown when the provider
                // is unable to authenticate due to the correct authorization header not
                // being set. We will throw an exception for this below.
            }
        }

        //验证未通过的异常
        $this->throwUnauthorizedException($exceptionStack);
    }

    /**
     * 从异常堆栈中抛出第一个异常
     *
     * @param array $exceptionStack
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return void
     */
    protected function throwUnauthorizedException(array $exceptionStack)
    {
        $exception = array_shift($exceptionStack);

        if ($exception === null) {
            //如果异常堆栈中没有异常，那就抛出http验证不通过异常
            $exception = new UnauthorizedHttpException(null, '认证失败，因为无效的身份请求头或无效的凭');
        }

        throw $exception;
    }

    /**
     * 从可用的服务提供者中过滤出服务提供者
     *
     * @param array $providers
     *
     * @return array
     */
    protected function filterProviders(array $providers)
    {
        if (empty($providers)) {
            return $this->providers;
        }

        return array_intersect_key($this->providers, array_flip($providers));
    }

    /**
     * Get the authenticated user.
     * 获取已验证的用户
     *
     * @param bool $authenticate
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUser($authenticate = true)
    {
        if ($this->user) {
            return $this->user;
        } elseif (! $authenticate) {
            return;
        }

        try {
            return $this->user = $this->authenticate();
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * getUser 方法的别名
     *
     * @param bool $authenticate
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public function user($authenticate = true)
    {
        return $this->getUser($authenticate);
    }

    /**
     * 设置一个已验证的用户
     *
     * @param \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model $user
     *
     * @return \Zyh\ApiServer\Auth\Auth
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * 检查用户是否已经验证通过(api)
     *
     * @param bool $authenticate
     *
     * @return bool
     */
    public function check($authenticate = false)
    {
        return ! is_null($this->user($authenticate));
    }

    /**
     * Get the provider used for authentication.
     * 获取已经使用过的关于验证的服务提供者
     *
     * @return \Zyh\ApiServer\Contract\Auth\Provider
     */
    public function getProviderUsed()
    {
        return $this->providerUsed;
    }

    /**
     * 使用一个自定义的服务提供者来做身份验证
     *
     * @param string          $key
     * @param object|callable $provider
     *
     * @return void
     */
    public function extend($key, $provider)
    {
        if (is_callable($provider)) {
            $provider = call_user_func($provider, $this->container);
        }

        $this->providers[$key] = $provider;
    }
}
