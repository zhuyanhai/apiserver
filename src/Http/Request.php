<?php
/**
 *  Http 请求类
 */

namespace Zyh\ApiServer\Http;

use Zyh\ApiServer\Http\Parser\Accept;
use Illuminate\Http\Request as IlluminateRequest;
use Zyh\ApiServer\Contract\Http\Request as RequestInterface;

class Request extends IlluminateRequest implements RequestInterface
{
    /**
     * Accept 请求报头解析类实例
     *
     * @var \Zyh\ApiServer\Http\Parser\Accept
     */
    protected static $acceptParser;

    /**
     * 请求报头类 Accept 的解析结果
     *
     * @var array
     */
    protected $accept;

    /**
     * 创建一个 Zyh 请求类实例
     *
     * @param \Illuminate\Http\Request $old
     *
     * @return \Zyh\ApiServer\Http\Request
     */
    public function createFromIlluminate(IlluminateRequest $old)
    {
        $new = new static(
            $old->query->all(), $old->request->all(), $old->attributes->all(),
            $old->cookies->all(), $old->files->all(), $old->server->all(), $old->content
        );

        //如果有session，就设置session
        if ($session = $old->getSession()) {
            $new->setSession($old->getSession());
        }

        //设置路由解析器
        $new->setRouteResolver($old->getRouteResolver());
        //设置用户解析器
        $new->setUserResolver($old->getUserResolver());

        return $new;
    }

    /**
     * 获取请求报头中的版本号，没有就获取默认定义的
     *
     * @return string
     */
    public function version()
    {
        $this->parseAcceptHeader();

        return $this->accept['version'];
    }

    /**
     * 获取请求报头中的子类型，没有就获取默认定义的
     *
     * @return string
     */
    public function subtype()
    {
        $this->parseAcceptHeader();

        return $this->accept['subtype'];
    }

    /**
     * 获取请求报头中的格式类型，没有就获取默认定义的
     *
     * @return string
     */
    public function format($default = 'html')
    {
        $this->parseAcceptHeader();

        return $this->accept['format'] ?: parent::format($default);
    }

    /**
     * 解析请求报头，并存储到 accept 属性中
     *
     * @return void
     */
    protected function parseAcceptHeader()
    {
        if ($this->accept) {
            return;
        }

        $this->accept = static::$acceptParser->parse($this);
    }

    /**
     * 设置一个 Accept 请求报头解析实例
     *
     * @param \Zyh\ApiServer\Http\Parser\Accept $acceptParser
     *
     * @return void
     */
    public static function setAcceptParser(Accept $acceptParser)
    {
        static::$acceptParser = $acceptParser;
    }

    /**
     * 获取请求报头解析实例
     *
     * @return \Zyh\ApiServer\Http\Parser\Accept
     */
    public static function getAcceptParser()
    {
        return static::$acceptParser;
    }
}
