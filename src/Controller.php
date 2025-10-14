<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 控制器基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\App;

/**
 * 控制器基础类
 */
abstract class Controller
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
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 控制器标题
     * @var string
     */
    protected $headTitle = '';

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
        // 如果是get请求
        if($this->request->isGet()){
            // 模板变量赋值
            $this->app->view->assign([
                'head_title' => $this->buildHeadTitle(),
            ]);
        }
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws \think\exception\ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
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

    /**
     * 生成网页头部标题
     * @access public
     * @param string $title 标题
     * @return string
     */
    protected function buildHeadTitle($title = null)
    {
        // 获取应用名称
        $appName = $this->app->config->get('app.app_name', '');
        // 如果指定的标题为null
        if(is_null($title)){
            // 读取路由定义里面指定的标题
            $ruleOptions = $this->request->rule()->getOption();
            // 路由指定了名称则使用路由名称, 未指定则使用控制器名称
            $title = isset($ruleOptions['head_title']) ? $ruleOptions['head_title'] : $this->headTitle;
        }
        // 标题为空则直接返回应用名称
        if(empty($title)){
            return $appName;
        }
        // 如果应用名称为空则直接返回标题
        if(empty($appName)){
            return $title;
        }

        // 不为空则拼接
        return $title . ' - ' . $appName;
    }

    /**
     * 返回空操作错误
     * @access  public
     * @param   string  $method   操作方法
     * @param   string  $args   请求参数
     * @return  \think\Exception
     */
    public function __call($method, $args)
    {
        // 如果是调试模式
        if($this->app->isDebug()){
            // 手动抛出异常
            throw new \think\Exception('method not exists:' . static::class . '->' . $method . '()', Response::ERROR);
        }
        // 返回404
        return $this->buildError(404);
    }
}
