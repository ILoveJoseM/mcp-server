<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Tools\ToolsManager;
use Illuminate\Support\Facades\Log;

/**
 * tools/list 策略
 * 列出所有可用的工具
 */
class ToolsListStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 工具管理器
     *
     * @var ToolsManager
     */
    protected $toolsManager;

    /**
     * 构造函数
     *
     * @param ToolsManager $toolsManager
     */
    public function __construct(ToolsManager $toolsManager)
    {
        $this->toolsManager = $toolsManager;
    }

    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'tools/list';
    }

    /**
     * 处理 tools/list 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        Log::info('MCP tools/list request', ['params' => $params]);

        // 获取所有工具
        $tools = $this->toolsManager->getAllTools();

        // 返回成功响应
        return $this->successResponse($id, [
            'tools' => $tools,
        ]);
    }

    /**
     * 验证 tools/list 请求参数
     * tools/list 不需要参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // tools/list 不需要任何参数,总是返回 true
        return true;
    }
}
