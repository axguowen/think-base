<?php
// +----------------------------------------------------------------------
// | type: trait
// +----------------------------------------------------------------------
// | name: 更新数据后自动更新缓存trait类
// +----------------------------------------------------------------------
// | Since: 2023-07-06
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base\concern;

/**
 * 自动更新缓存
 */
trait AutoUpdateCache
{
    /**
     * 保存写入数据
     * @overwrite
     * @access protected
     * @return bool
     */
    protected function updateData(): bool
    {
        if(!parent::updateData()){
            return false;
        }
        // 更新缓存
        $this->updateCache();
        // 返回
        return true;
    }
}
