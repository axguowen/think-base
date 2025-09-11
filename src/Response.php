<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 响应对象基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\Response as Base;

class Response extends Base
{
    // 成功
    const SUCCESS = 0;
    // 未登录
    const NOT_LOGIN = 100;
    // 已登录
    const ALREADY_LOGINED = 101;
    // 失败
    const FAILED = 400;
    // 参数错误
    const PARAMS_ERROR = 401;
    // 禁止响应
    const FORBIDDEN = 403;
    // 数据不存在
    const NOT_FOUND = 404;
    // 错误
    const ERROR = 500;
    // 没有权限
    const NOACCESS = 600;

    /**
     * 代码错误信息映射
     * @access public
     * @param int $code
     * @return string
     */
    public static function getErrorMessage($code = 0)
    {
        // 代码错误信息映射
        $codeMessageMap = [
            static::SUCCESS => '操作成功',
            static::NOT_LOGIN => '未登录',
            static::ALREADY_LOGINED => '已登录',
            static::FAILED => '操作失败',
            static::PARAMS_ERROR => '参数错误',
            static::FORBIDDEN => '请求被禁止',
            static::NOT_FOUND => '数据不存在',
            static::ERROR => '发生错误',
            static::NOACCESS => '没有权限',
        ];
        // 错误信息
        $errorMessage = '未知错误';
        // 如果存在错误码
        if(isset($codeMessageMap[$code])){
            // 获取错误信息
            $errorMessage = $codeMessageMap[$code];
        }
        // 返回
        return $errorMessage;
    }

    /**
     * 错误码字段名
     * @var string
     */
    protected static $jsonResponseFieldCode = 'code';

    /**
     * 错误信息字段名
     * @var string
     */
    protected static $jsonResponseFieldMessage = 'message';

    /**
     * 数据字段名
     * @var string
     */
    protected static $jsonResponseFieldData = 'data';

    /**
     * 响应成功
     * @access public
     * @param array $data 返回数据
     * @param string $message 错误信息
     * @return \think\response\Json
     */
    public static function success($data = [], $message = null)
    {
        // 返回
        return static::failed($message, static::SUCCESS, $data);
    }

    /**
     * 响应失败
     * @access public
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param array $data 返回数据
     * @return mixed
     */
    public static function failed($message = null, $code = null, $data = [], $type = 'json')
    {
        // 未指定错误码
        if(is_null($code)){
            $code = static::FAILED;
        }
        // 未指定错误信息
        if(is_null($message)){
            // 获取错误码对应的错误信息
            $message = static::getErrorMessage($code);
        }
        // 如果类型是数组
        if($type == 'array'){
            // 返回数组
            return [
                static::$jsonResponseFieldCode => $code,
                static::$jsonResponseFieldMessage => $message,
                static::$jsonResponseFieldData => $data,
            ];
        }
        // 返回
        return static::create([
            static::$jsonResponseFieldCode => $code,
            static::$jsonResponseFieldMessage => $message,
            static::$jsonResponseFieldData => $data,
        ], $type);
    }

    /**
     * 抛出异常
     * @access public
     * @param string $message 错误信息
     * @param int $code 错误码
     * @return void
     * @throws \think\Exception
     */
    public static function exception($message = null, $code = null)
    {
        // 未指定code
        if(is_null($code)){
            $code = static::ERROR;
        }
        // 未指定提示信息
        if(is_null($message)){
            // 获取错误码对应的错误信息
            $message = static::getErrorMessage($code);
        }
        // 抛出异常
        throw new \think\Exception($message, $code);
    }

    /**
     * 返回数组
     * @access public
     * @param array $data 返回数据
     * @param string $message 错误信息
     * @param int $code 返回码
     * @return array
     */
    public static function array($data = [], $message = null, $code = null)
    {
        // 未指定code
        if(is_null($code)){
            $code = static::SUCCESS;
        }
        // 返回
        return static::failed($message, $code, $data, 'array');
    }

    /**
     * 返回模板渲染
     * @access public
     * @param string $template 模板文件
     * @param array $vars 模板变量
     * @param int $code 状态码
     * @param callable $filter 内容过滤
     * @return \think\response\View
     */
    public static function view($template = '', $vars = [], $code = 200, $filter = null)
    {
        // 返回
        return static::create($template, 'view', $code)->assign($vars)->filter($filter);
    }

    /**
     * 返回内容渲染
     * @access public
     * @param string $content 渲染内容
     * @param array $vars 模板变量
     * @param int $code 状态码
     * @param callable $filter 内容过滤
     * @return \think\response\View
     */
    public static function display($content = '', $vars = [], $code = 200, $filter = null)
    {
        // 返回
        return static::create($content, 'view', $code)->isContent(true)->assign($vars)->filter($filter);
    }

    /**
     * 返回跳转页面
     * @access public
     * @param string $url 重定向地址
     * @param int $code 状态码
     * @return \think\response\Html
     */
    public static function redirect($url = '', $code = 302)
    {
        // 如果url为空
        if(empty($url)){
            $url = \think\facade\App::get('request')->baseFile();
        }
        // 构造返回数据
        $data = '<script>window.top.location.href="' . $url . '";</script>';
        // 返回响应
        return static::create($data, 'html', $code);
    }

    /**
     * 返回错误页
     * @access public
     * @param int $code 状态码
     * @return \think\response\Html
     */
    public static function error($code = 404)
    {
        // 状态码标题映射
        $codeMessageMap = [
            403 => '403 Forbidden',
            404 => '404 Not Found',
            500 => '500 Internal Server Error',
            502 => '502 Bad Gateway',
            503 => '503 Service Unavailable',
        ];
        // 如果未指定状态码
        if(is_null($code)){
            $code = 404;
        }
        // 获取状态码标题
        $title = isset($codeMessageMap[$code]) ? $codeMessageMap[$code] : '404 Not Found';
        // 渲染的内容
        $content = '<html><head><title>' . $title . '</title></head><body><center><h1>' . $title . '</h1></center><hr><center>Nginx</center></body></html>';
        // 返回
        return static::create($content, 'html', $code);
    }

    /**
     * 直接显示内容
     * @access public
     * @param string $content 渲染内容
     * @param bool $debug 是否调试
     * @return string
     */
    public static function echo($content = '', $debug = false)
    {
        // 如果是调试
        if(true === $debug){
            // 直接返回
            return $content;
        }
        // 返回
        return static::create($content)->contentType('text/plain');
    }
}
