<?php
/**
 * Http 请求报头解析
 */

namespace Zyh\ApiServer\Http\Parser;

use Illuminate\Http\Request;
use Zyh\ApiServer\Contract\Http\Parser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Accept implements Parser
{
    /**
     * 标准树
     *
     * @var string
     */
    protected $standardsTree;

    /**
     * API 子类型
     *
     * @var string
     */
    protected $subtype;

    /**
     * 默认版本号
     *
     * @var string
     */
    protected $version;

    /**
     * 默认格式
     *
     * @var string
     */
    protected $format;

    /**
     * 创建一个 请求报头解析实例
     *
     * @param string $standardsTree
     * @param string $subtype
     * @param string $version
     * @param string $format
     *
     * @return void
     */
    public function __construct($standardsTree, $subtype, $version, $format)
    {
        $this->standardsTree = $standardsTree;
        $this->subtype        = $subtype;
        $this->version        = $version;
        $this->format         = $format;
    }

    /**
     * 解析请求报头，如果使用严格模式，那么请求报头必须是一个可用的并且必须是一个有效匹配的
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $strict
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return array
     */
    public function parse(Request $request, $strict = false)
    {
        $pattern = '/application\/'.$this->standardsTree.'\.('.$this->subtype.')\.([\w\d\.\-]+)\+([\w]+)/';

        //检测请求报头是否匹配
        if (! preg_match($pattern, $request->header('accept'), $matches)) {//不匹配

            if ($strict) {//严格模式，抛异常
                throw new BadRequestHttpException('在严格匹配模式下，请求报头不能正确解析');
            }

            //非严格模式，使用默认设置的请求报头
            $default = 'application/'.$this->standardsTree.'.'.$this->subtype.'.'.$this->version.'+'.$this->format;

            preg_match($pattern, $default, $matches);
        }

        //构建出新数组返回
        return array_combine(['subtype', 'version', 'format'], array_slice($matches, 1));
    }
}
