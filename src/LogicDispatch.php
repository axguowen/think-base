<?php
// +----------------------------------------------------------------------
// | Type: logic
// +----------------------------------------------------------------------
// | Name: 逻辑层调度基础类
// +----------------------------------------------------------------------
// | Since: 2026-02-05
// +----------------------------------------------------------------------
// | Copyright (c) 2018-2025 http://itzjj.com All rights reserved.
// +----------------------------------------------------------------------

namespace think\base;

use think\App;
use think\Container;

/**
 * 逻辑层调度基础类
 */
abstract class LogicDispatch
{
    /**
     * 应用对象
     * @var App
     */
    protected $app;

    /**
     * 控制器名
     * @var string
     */
    protected $controller;

    /**
     * 方法名
     * @var string
     */
    protected $method;

    /**
     * 参数
     * @var array
     */
    protected $param;

    /**
     * 构造方法
     * @access public
     * @param App $app
     * @param array $param
     */
    public function __construct(App $app, $controller, $method, array $param = [])
    {
        $this->app  = $app;
        $this->controller = $controller;
        $this->method = $method;
        $this->param = $param;
        // 初始化
        $this->init();
    }

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function init()
    {
    }

    /**
     * 执行逻辑层调度
     * @access public
     * @return mixed
     */
    public function run(){
        // 返回执行结果
        return $this->exec();
    }

    /**
     * 获取控制器名
     * @access public
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 获取方法名
     * @access public
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取参数
     * @access public
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * 执行调度
     * @access protected
     * @return mixed
     */
    abstract protected function exec();

    public function __sleep()
    {
        return ['controller', 'method', 'param'];
    }

    public function __wakeup()
    {
        $this->app = Container::pull('app');
    }

    public function __debugInfo()
    {
        return [
            'controller' => $this->controller,
            'method' => $this->method,
            'param' => $this->param,
        ];
    }
}
