<?php

namespace Zyh\ApiServer\Routing;

use Illuminate\Routing\ResourceRegistrar as IlluminateResourceRegistrar;

class ResourceRegistrar extends IlluminateResourceRegistrar
{
    /**
     * The default actions for a resourceful controller. Excludes 'create' and 'edit'.
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'store', 'show', 'update', 'destroy'];

    /**
     * Create a new resource registrar instance.
     *
     * @param \Zyh\ApiServer\Routing\Router $router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
}
