<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Tools\ToolsManager;
use Illuminate\Support\Facades\Log;

/**
 * ping 策略
 * 调用指定的工具
 */
class PingStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'ping';
    }

    /**
     * 处理 ping 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        // 返回成功响应
        return $this->successResponse($id, []);
    }

    /**
     * 默认验证实现
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        return true;
    }
}
