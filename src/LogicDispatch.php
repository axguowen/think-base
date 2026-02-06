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
     * 调度信息
     * @var mixed
     */
    protected $dispatch;

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
    public function __construct(App $app, $dispatch, array $param = [])
    {
        $this->app  = $app;
        $this->dispatch = $dispatch;
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
     * 获取调度信息
     * @access public
     * @return mixed
     */
    public function getDispatch()
    {
        return $this->dispatch;
    }

    /**
     * 获取调度信息
     * @access public
     * @return mixed
     */
    public function getParam(): array
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
        return ['dispatch', 'param'];
    }

    public function __wakeup()
    {
        $this->app = Container::pull('app');
    }

    public function __debugInfo()
    {
        return [
            'dispatch' => $this->dispatch,
            'param' => $this->param,
        ];
    }
}
