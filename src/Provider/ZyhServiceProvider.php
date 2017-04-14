<?php

namespace Zyh\ApiServer\Provider;

use RuntimeException;
use Zyh\ApiServer\Auth\Auth;
use Zyh\ApiServer\Dispatcher;
use Zyh\ApiServer\Http\Request;
use Zyh\ApiServer\Http\Response;
use Zyh\ApiServer\Console\Command;
use Zyh\ApiServer\Exception\Handler as ExceptionHandler;

class ZyhServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setResponseStaticInstances();

        Request::setAcceptParser($this->app['Zyh\ApiServer\Http\Parser\Accept']);

        $this->app->rebinding('api.routes', function ($app, $routes) {
            $app['api.url']->setRouteCollections($routes);
        });
    }

    protected function setResponseStaticInstances()
    {
        Response::setFormatters($this->config('formats'));
        Response::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->registerClassAliases();

        $this->app->register(RoutingServiceProvider::class);

        $this->app->register(HttpServiceProvider::class);

        $this->registerExceptionHandler();

        $this->registerDispatcher();

        $this->registerAuth();

        $this->registerDocsCommand();

        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->commands([
                'Zyh\ApiServer\Console\Command\Cache',
                'Zyh\ApiServer\Console\Command\Routes',
            ]);
        }
    }

    /**
     * Register the configuration.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(realpath(__DIR__.'/../../config/api.php'), 'api');

        if (! $this->app->runningInConsole() && empty($this->config('prefix')) && empty($this->config('domain'))) {
            throw new RuntimeException('Unable to boot ApiServiceProvider, configure an API domain or prefix.');
        }
    }

    /**
     * Register the class aliases.
     *
     * @return void
     */
    protected function registerClassAliases()
    {
        $aliases = [
            'Zyh\ApiServer\Http\Request' => 'Zyh\ApiServer\Contract\Http\Request',
            'api.dispatcher' => 'Zyh\ApiServer\Dispatcher',
            'api.http.validator' => 'Zyh\ApiServer\Http\RequestValidator',
            'api.http.response' => 'Zyh\ApiServer\Http\Response\Factory',
            'api.router' => 'Zyh\ApiServer\Routing\Router',
            'api.router.adapter' => 'Zyh\ApiServer\Contract\Routing\Adapter',
            'api.auth' => 'Zyh\ApiServer\Auth\Auth',
            'api.limiting' => 'Zyh\ApiServer\Http\RateLimit\Handler',
            'api.url' => 'Zyh\ApiServer\Routing\UrlGenerator',
            'api.exception' => ['Zyh\ApiServer\Exception\Handler', 'Zyh\ApiServer\Contract\Debug\ExceptionHandler'],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app->singleton('api.exception', function ($app) {
            return new ExceptionHandler($app['Illuminate\Contracts\Debug\ExceptionHandler'], $this->config('errorFormat'), $this->config('debug'));
        });
    }

    /**
     * Register the internal dispatcher.
     *
     * @return void
     */
    public function registerDispatcher()
    {
        $this->app->singleton('api.dispatcher', function ($app) {
            $dispatcher = new Dispatcher($app, $app['files'], $app['Zyh\ApiServer\Routing\Router'], $app['Zyh\ApiServer\Auth\Auth']);

            $dispatcher->setSubtype($this->config('subtype'));
            $dispatcher->setStandardsTree($this->config('standardsTree'));
            $dispatcher->setPrefix($this->config('prefix'));
            $dispatcher->setDefaultVersion($this->config('version'));
            $dispatcher->setDefaultDomain($this->config('domain'));
            $dispatcher->setDefaultFormat($this->config('defaultFormat'));

            return $dispatcher;
        });
    }

    /**
     * Register the auth.
     *
     * @return void
     */
    protected function registerAuth()
    {
        $this->app->singleton('api.auth', function ($app) {
            return new Auth($app['Zyh\ApiServer\Routing\Router'], $app, $this->config('auth'));
        });
    }

    /**
     * Register the documentation command.
     *
     * @return void
     */
    protected function registerDocsCommand()
    {
        $this->app->singleton('Zyh\ApiServer\Console\Command\Docs', function ($app) {
            return new Command\Docs(
                $app['Zyh\ApiServer\Routing\Router'],
                $app['Dingo\Blueprint\Blueprint'],
                $app['Dingo\Blueprint\Writer'],
                $this->config('name'),
                $this->config('version')
            );
        });

        $this->commands(['Zyh\ApiServer\Console\Command\Docs']);
    }
}
