<?php

namespace Zyh\ApiServer\Contract\Transformer;

use Zyh\ApiServer\Http\Request;
use Zyh\ApiServer\Transformer\Binding;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed                          $response
     * @param object                         $transformer
     * @param \Zyh\ApiServer\Transformer\Binding $binding
     * @param \Zyh\ApiServer\Http\Request        $request
     *
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request);
}
