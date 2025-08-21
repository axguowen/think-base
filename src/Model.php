<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 模型基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\Model as Base;

abstract class Model extends Base
{
    /**
     * 缓存连接
     * @var string
     */
    protected $cacheConnection = '';

    /**
     * 缓存键规则
     * @var string
     */
    protected $cacheKey = '';

    /**
     * 额外缓存键规则
     * @var array
     */
    protected $extraCacheKeys = [];

    /**
     * 数据非法时的缓存值, 防止缓存穿透
     * @var string
     */
    protected $cacheInvalidValue = 'data invalid';

    /**
     * 不缓存的字段
     * @var array
     */
    protected $cacheFieldExcept = [];

    /**
     * 缓存时间
     * @var int
     */
    protected $cacheExpire = 0;

    /**
     * 获取缓存客户端
     * @access protected
     * @return \think\redisclient\Connection|null
     */
    protected function getCacheClient()
    {
        // 未设置缓存连接
        if(empty($this->cacheConnection) || empty($this->cacheKey)){
            return null;
        }
        return \think\facade\RedisClient::connect($this->cacheConnection);
    }

    /**
     * 数据缓存键名构造器
     * @access public
     * @param string $cacheName 指定缓存标识
     * @return \think\redisclient\Builder|null
     */
    public function getCacheBuilder($cacheName = null)
    {
        // 获取缓存键名
        $cacheKey = $this->cacheKey;
        // 如果指定了缓存标识
        if(!is_null($cacheName)){
            // 如果不存在额外缓存键规则
            if(!isset($this->extraCacheKeys[$cacheName]) || empty($this->extraCacheKeys[$cacheName])){
                return null;
            }
            // 设置缓存键名
            $cacheKey = $this->extraCacheKeys[$cacheName];
        }
        // 获取缓存客户端
        $cacheClient = $this->getCacheClient();
        // 如果返回空
        if(is_null($cacheClient)){
            return null;
        }
        // 返回
        return $cacheClient->key($cacheKey, $this->getData());
    }

    /**
     * 通过缓存获取单个数据
     * @access public
     * @param mixed $data 查询数据
     * @param string $cacheName 指定缓存标识
     * @return mixed
     */
    public static function find($data = null, $cacheName = null)
    {
        // 实例化当前模型
        $model = new static();
        // 如果数据为空
        if(is_null($data)){
            return call_user_func_array([$model->db(), 'find'], [$data]);
        }
        // 缓存客户端
        $cacheClient = $model->getCacheClient();
        // 不存在
        if(is_null($cacheClient) && is_numeric($data)){
            return call_user_func_array([$model->db(), 'find'], [$data]);
        }
        // 构造原始数据
        $originData = is_array($data) ? $data : [$model->getPk() => $data];
        // 设置原始数据
        $model->data($originData);
        // 获取缓存数据
        $cacheData = $model->findCacheData($cacheName);
        // 如果不为空
        if(!empty($cacheData)){
            // 如果请求的数据为无效的
            if($cacheData == $model->cacheInvalidValue){
                return null;
            }
            // 更新条件
            $updateWhere = [];
            foreach($originData as $k => $v){
                $updateWhere[] = [$k, '=', $v];
            }
            // 返回新的实例
            return $model->newInstance($cacheData, $updateWhere);
        }
        // 返回空则从数据库读取
        return $model->findDbData(is_null($cacheData), $cacheName);
    }

    /**
     * 读取缓存数据
     * @access protected
     * @param string $cacheName 指定缓存标识
     * @return mixed
     */
    protected function findCacheData($cacheName = null)
    {
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder($cacheName);
        // 不存在
        if(is_null($cacheBuilder)){
            return null;
        }
        // 如果键名不存在
        if(!$cacheBuilder->exists()){
            return false;
        }
        // 键名存在但不是hash类型
        if(5 !== $cacheBuilder->type()){
            return $this->cacheInvalidValue;
        }
        // 返回缓存数据
        return $cacheBuilder->hGetAll();
    }

    /**
     * 从数据库查询数据并更新缓存
     * @access protected
     * @param bool $disableCache 是否禁用缓存
     * @param string $cacheName 指定缓存标识
     * @return mixed
     */
    protected function findDbData($disableCache = false, $cacheName = null)
    {
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder($cacheName);

        // 如果缓存键名构造器不存在或者禁用了缓存则直接从数据库读取并返回
        if(is_null($cacheBuilder) || $disableCache){
            return static::where($this->getData())->find();
        }

        // 获取缓存客户端
        $cacheClient = $this->getCacheClient();
        // 写缓存锁构造器
        $lockBuilder = $cacheClient->key('setcachelock:' . $cacheBuilder->getKey());

        $time = time();
        while ($time + 5 > time() && $lockBuilder->exists()) {
            // 存在锁定则等待
            usleep(200000);
        }

        // 模型定义
        $model = null;

        try {
            // 锁定
            $lockBuilder->set(1);

            // 从数据库查询数据
            $model = static::where($this->getData())->find();
            // 如果查询为空
            if(empty($model)){
                // 写入非法数据缓存结果
                $cacheBuilder->setex(300, $this->cacheInvalidValue);
            }
            else{
                // 查询到则更新缓存
                $model->updateCache();
            }
        } catch (\Exception | \throwable $e) {
            throw $e;
        } finally {
            // 解锁
            $lockBuilder->del();
        }

        // 返回
        return $model;
    }

    /**
     * 更新缓存数据
     * @access public
     * @param array $data 要更新的数据
     * @return bool
     */
    public function updateCache(array $data = [])
    {
        // 要更新的数据
        $updateData = $data;
        // 如果当前指定的数据为空则更新全部缓存字段
        if(empty($updateData)){
            $updateData = $this->getData();
        }
        // 不缓存的字段
        foreach ($this->cacheFieldExcept as $key) {
            if (array_key_exists($key, $updateData)) {
                unset($updateData[$key]);
            }
        }
        // 获取缓存标识列表
        $cacheNames = [null];
        // 遍历额外缓存标识
        foreach ($this->extraCacheKeys as $key => $value) {
            // 记录
            $cacheNames[] = $key;
        }
        // 遍历缓存标识列表
        foreach ($cacheNames as $cacheName) {
            // 缓存键名构造器
            $cacheBuilder = $this->getCacheBuilder($cacheName);
            // 不存在
            if(is_null($cacheBuilder)){
                continue;
            }
            // 如果缓存键名不存在且指定了要缓存的数据则返回
            if(!$cacheBuilder->exists() && !empty($data)){
                continue;
            }
            // 如果缓存键名存在但是无效
            if($cacheBuilder->exists() && 5 !== $cacheBuilder->type()){
                // 删除缓存
                $cacheBuilder->del();
                continue;
            }
            // 更新缓存数据
            $updateStatus = $cacheBuilder->hMset($updateData);
            // 设置了缓存时间则更新缓存时间
            if($updateStatus && $this->cacheExpire > 0){
                $cacheBuilder->expire($this->cacheExpire);
            }
        }
        // 返回
        return true;
    }

    /**
     * 删除缓存数据
     * @access public
     * @return bool
     */
    public function deleteCache()
    {
        // 获取缓存标识列表
        $cacheNames = [null];
        // 遍历额外缓存标识
        foreach ($this->extraCacheKeys as $key => $value) {
            // 记录
            $cacheNames[] = $key;
        }
        // 遍历缓存标识列表
        foreach ($cacheNames as $cacheName) {
            // 缓存键名构造器
            $cacheBuilder = $this->getCacheBuilder($cacheName);
            // 不存在
            if(is_null($cacheBuilder)){
                continue;
            }
            // 缓存不存在
            if(!$cacheBuilder->exists()){
                continue;
            }
            // 删除缓存
            $cacheBuilder->unlink();
        }
        // 返回
        return true;
    }

    // +=======================
    // | 模型获取器 S
    // +=======================

    // +=======================
    // | 重写父类方法 S
    // +=======================
    /**
     * 设置需要隐藏的输出属性
     * @access public
     * @param array $hidden 属性列表
     * @return $this
     */
    public function hidden(array $hidden = [], $merge = true)
    {
        return parent::hidden($hidden, $merge);
    }
}
