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
    const HAS_LOGINED = 101;
    // 需要短信验证
    const NEED_SMS_VERIFY = 201;
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
     * 提示信息列表
     * @var array
     */
    public static $errorMessage = [
        self::SUCCESS => '操作成功',
        self::NOT_LOGIN => '未登录',
        self::HAS_LOGINED => '已登录',
        self::NEED_SMS_VERIFY => '需要完成短信验证',
        self::FAILED => '操作失败',
        self::PARAMS_ERROR => '参数错误',
        self::FORBIDDEN => '禁止响应',
        self::NOT_FOUND => '数据不存在',
        self::ERROR => '发生错误',
        self::NOACCESS => '没有权限',
    ];

    /**
     * 返回成功响应信息
     * @access public
     * @param array $data 返回数据
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    public function buildSuccess($data = [], $message = null)
    {
        // 返回
        return $this->buildFailed(self::SUCCESS, $message, $data);
    }

    /**
     * 返回失败响应信息
     * @access public
     * @param int|string $code 错误码
     * @param string $message 提示信息
     * @param array $data 返回数据
     * @return \think\response\Json
     */
    public function buildFailed($code = null, $message = null, $data = [])
    {
        // 未指定错误码
        if(is_null($code)){
            $code = self::FAILED;
        }
        // 如果错误码是字符串
        elseif(is_string($code) && !is_numeric($code)){
            // 获取后面指定的错误码
            $errCode = $message;
            // 指定错误信息
            $message = $code;
            // 默认错误码
            $code = is_null($errCode) || !is_numeric($errCode) ? self::FAILED : $errCode;
        }
        // 未指定错误信息
        if(is_null($message)){
            $message = $this->getMessage($code);
        }
        // 返回
        return self::create([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], 'json');
    }

    /**
     * 手动抛出异常信息
     * @access public
     * @param int $code 错误码
     * @param string $message 提示信息
     * @return void
     * @throws \think\Exception
     */
    public function buildException($code = null, $message = null)
    {
        // 未指定code
        if(is_null($code)){
            $code = self::ERROR;
        }
        // 未指定提示信息
        if(is_null($message)){
            $message = $this->getMessage($code);
        }
        // 抛出异常
        throw new \think\Exception($message, $code);
    }

    /**
     * 构造返回数组
     * @access public
     * @param array $data 返回数据
     * @param int $code 返回码
     * @param string $message 提示信息
     * @return array
     */
    public function buildArray($data = [], $code = null, $message = null)
    {
        // 未指定code
        if(is_null($code)){
            $code = self::SUCCESS;
        }
        // 未指定提示信息
        if(is_null($message)){
            $message = $this->getMessage($code);
        }
        // 返回
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 构造渲染模板输出
     * @access public
     * @param string $template 模板文件
     * @param array $vars 模板变量
     * @param int $code 状态码
     * @param callable $filter 内容过滤
     * @return \think\response\View
     */
    public function buildView(string $template = '', $vars = [], int $code = 200, $filter = null)
    {
        // 返回
        return self::create($template, 'view', $code)->assign($vars)->filter($filter);
    }

    /**
     * 构造渲染内容输出
     * @access public
     * @param string $content 渲染内容
     * @param array $vars 模板变量
     * @param int $code 状态码
     * @param callable $filter 内容过滤
     * @return \think\response\View
     */
    public function buildDisplay(string $content = '', $vars = [], int $code = 200, $filter = null)
    {
        // 返回
        return self::create($content, 'view', $code)->isContent(true)->assign($vars)->filter($filter);
    }

    /**
     * 手动返回验证错误响应信息
     * @access public
     * @param array|string $error 错误信息
     * @return \think\response\Json
     */
    public function buildValidateFailed($error)
    {
        // 错误信息是数组
        if(is_array($error)){
            return self::create($error, 'json');
        }
        return $this->buildFailed(self::FAILED, $error);
    }

    /**
     * 重定向输出
     * @access public
     * @param string $url 重定向地址
     * @param int $code 状态码
     * @return \think\response\Html
     */
    public function buildRedirect(string $url = '', int $code = 302)
    {
        // 如果url为空
        if(empty($url)){
            $url = \think\facade\App::get('request')->baseFile();
        }
        // 构造返回数据
        $data = '<script>window.top.location.href="' . $url . '";</script>';
        // 返回响应
        return self::create($data, 'html', $code);
    }

    /**
     * 错误页输出
     * @access public
     * @param int $code 状态码
     * @return \think\response\View
     */
    public function buildError(int $code = 404)
    {
        // 遍历
        switch($code){
            case 403:
                $title = '403 Forbidden';
                break;
            case 404:
                $title = '404 Not Found';
                break;
            case 500:
                $title = '500 Internal Server Error';
                break;
            case 502:
                $title = '502 Bad Gateway';
                break;
            case 503:
                $title = '503 Service Unavailable';
                break;
            default:
                $title = '404 Not Found';
        }
        // 渲染的内容
        $content = '<html><head><title>' . $title . '</title></head><body><center><h1>' . $title . '</h1></center><hr><center>Nginx</center></body></html>';
        // 返回
        return self::create($content, 'view', $code)->isContent(true);
    }

    /**
     * 直接显示内容
     * @access public
     * @param string $content 渲染内容
     * @param bool $debug 是否调试
     * @return string
     */
    public function buildEcho(string $content = '', $debug = false)
    {
        // 如果是调试
        if(true === $debug){
            return $content;
        }
        // 返回
        echo $content;
        exit();
    }

    /**
     * 获取错误信息
     * @access protected
     * @param int $code 状态码
     * @return string
     */
    protected function getMessage(int $code): string
    {
        // 存在错误信息
        if(isset(self::$errorMessage[$code])){
            return self::$errorMessage[$code];
        }
        // 不存在
        return '未知错误';
    }
}
