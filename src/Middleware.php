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
        return Response::success($data, $message);
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
        return Response::failed($message, $code, $data);
    }

    /**
     * 响应成功
     * @access protected
     * @param array $data 返回数据
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    protected function buildSuccess($data = [], $message = null)
    {
        return Response::success($data, $message);
    }

    /**
     * 响应失败
     * @access protected
     * @param int $code 错误码
     * @param string $message 提示信息
     * @param array $data 返回数据
     * @return \think\response\Json
     */
    protected function buildFailed($code = null, $message = null, $data = [])
    {
        return Response::failed($message, $code, $data);
    }
}