<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 多对多中间模型基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\model\Pivot as Base;

abstract class Pivot extends Base
{
    // +=======================
    // | 重写父类方法 S
    // +=======================
    /**
     * 设置需要隐藏的输出属性
     * @access public
     * @param  array $hidden   属性列表
     * @return $this
     */
    public function hidden(array $hidden = [], $merge = true)
    {
        return parent::hidden($hidden, $merge);
    }
}
