<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 中间件基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\App;

abstract class Middleware
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 响应类名
     * @var string
     */
    protected $responseClassName = Response::class;

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
    }

    /**
     * 响应成功
     * @access protected
     * @param array $data 返回数据
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    protected function jsonSuccess($data = [], $message = null)
    {
        return call_user_func_array([$this->responseClassName, 'success'], [$data, $message]);
    }

    /**
     * 响应失败
     * @access protected
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param array $data 返回数据
     * @return \think\response\Json
     */
    protected function jsonFailed($message = null, $code = null, $data = [])
    {
        return call_user_func_array([$this->responseClassName, 'failed'], [$message, $code, $data]);
    }

    /**
     * 重定向输出
     * @access protected
     * @param string $url 重定向地址
     * @param int $code 状态码
     * @return \think\response\Html
     */
    protected function buildRedirect(string $url = '', int $code = 302)
    {
        return call_user_func_array([$this->responseClassName, 'redirect'], [$url, $code]);
    }

    /**
     * 错误页输出
     * @access public
     * @param int $code 状态码
     * @return \think\response\View
     */
    public function buildError(int $code = 404)
    {
        return call_user_func_array([$this->responseClassName, 'error'], [$code]);
    }

    /**
     * 内容直接输出
     * @access public
     * @param string $content
     * @param bool $debug
     * @return string
     */
    protected function buildEcho($content = '', $debug = false)
    {
        return call_user_func_array([$this->responseClassName, 'echo'], [$content, $debug]);
    }
}