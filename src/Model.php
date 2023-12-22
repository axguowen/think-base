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
     * 数据缓存键名构造器字段获取器
     * @param string $value 字段原始值
     * @param array $data 当前所有数据
     * @return \think\redisclient\Builder|null
     */
    public function getCacheBuilder()
    {
        // 获取缓存客户端
        $cacheClient = $this->getCacheClient();
        // 如果返回空
        if(is_null($cacheClient)){
            return null;
        }
        // 返回
        return $cacheClient->key($this->cacheKey, $this->getData());
    }

    /**
     * 通过缓存获取单个数据
     * @access public
     * @param mixed $data 查询数据
     * @return mixed
     */
    public static function find($data = null)
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
        $cacheData = $model->findCacheData();
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
        return $model->findDbData(is_null($cacheData));
    }

    /**
     * 读取缓存数据
     * @access protected
     * @return mixed
     */
    protected function findCacheData()
    {
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder();
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
     * @return mixed
     */
    protected function findDbData($disableCache = false)
    {
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder();

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
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder();
        // 不存在
        if(is_null($cacheBuilder)){
            return false;
        }
        // 如果缓存键名不存在且指定了要缓存的数据则返回
        if(!$cacheBuilder->exists() && !empty($data)){
            return false;
        }
        // 如果缓存键名存在但是无效
        if($cacheBuilder->exists() && 5 !== $cacheBuilder->type()){
            $cacheBuilder->del();
        }
        // 如果当前指定的数据为空则更新全部缓存字段
        if(empty($data)){
            $data = $this->getData();
        }

        // 不缓存的字段
        foreach ($this->cacheFieldExcept as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }

        // 写入正常缓存
        $updateStatus = $cacheBuilder->hMset($data);
        // 缓存时间大于0
        if($updateStatus && $this->cacheExpire > 0){
            $cacheBuilder->expire($this->cacheExpire);
        }
        // 返回状态
        return $updateStatus;
    }

    /**
     * 删除缓存数据
     * @access public
     * @return bool
     */
    public function deleteCache()
    {
        // 缓存键名构造器
        $cacheBuilder = $this->getCacheBuilder();
        // 不存在
        if(is_null($cacheBuilder)){
            return false;
        }
        // 缓存不存在
        if(!$cacheBuilder->exists()){
            return true;
        }
        // 返回状态
        return $cacheBuilder->unlink();
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
