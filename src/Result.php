<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 命令基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

class Result
{
    // 成功状态码
    const SUCCESS_CODE = 0;

    /**
     * 结果数据
     * @var mixed
     */
    protected $data;

    /**
     * 结果信息
     * @var string
     */
    protected $message;

    /**
     * 错误码
     * @var int
     */
    protected $code;

    /**
     * 构造方法
     * @access protected
     */
    protected function __construct($data, string $message, int $code)
    {
        // 赋值
        $this->data = $data;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * 创建成功的Result对象
     * @access public
     * @param mixed $data 结果数据
     * @param string $message 结果信息
     * @return static
     */
    public static function success($data = [], string $message = ''): Result
    {
        // 如果信息为空
        if (empty($message)) {
            $message = '操作成功';
        }
        return new static($data, $message, static::SUCCESS_CODE);
    }

    /**
     * 创建失败的Result对象
     * @access public
     * @param mixed $data 结果数据
     * @param string $message 结果信息
     * @param int $code 状态码
     * @return static
     */
    public static function failed(string $message = '', int $code = 400, $data = []): Result
    {
        // 如果信息为空
        if (empty($message)) {
            $message = '操作失败';
        }
        // 如果状态码为空
        if (empty($code)) {
            $code = 400;
        }
        return new static($data, $message, $code);
    }

    /**
     * 是否成功
     * @access public
     * @return bool
     */
    public function isSuccess(): bool
    {
        return static::SUCCESS_CODE === $this->code;
    }

    /**
     * 获取结果数据
     * @access public
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取结果信息
     * @access public
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 获取状态码
     * @access public
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 转换为响应数据
     * @access public
     * @param string $type
     * @return Response
     */
    public function toResponse($type = 'json'): Response
    {
        return Response::create([
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ], $type);
    }

    /**
     * 转换为数组数据
     * @access public
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
