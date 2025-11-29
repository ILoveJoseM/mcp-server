<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Prompts\PromptsManager;
use Illuminate\Support\Facades\Log;

/**
 * prompts/list 策略
 * 列出所有可用的提示词
 */
class PromptsListStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 提示词管理器
     *
     * @var PromptsManager
     */
    protected $promptsManager;

    /**
     * 构造函数
     *
     * @param PromptsManager $promptsManager
     */
    public function __construct(PromptsManager $promptsManager)
    {
        $this->promptsManager = $promptsManager;
    }

    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'prompts/list';
    }

    /**
     * 处理 prompts/list 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            Log::info('MCP prompts/list request');

            // 获取所有提示词
            $prompts = $this->promptsManager->getAllPrompts();

            Log::info('Prompts listed', [
                'count' => count($prompts),
            ]);

            // 返回成功响应
            return $this->successResponse($id, [
                'prompts' => $prompts,
            ]);

        } catch (\Exception $e) {
            Log::error('prompts/list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    /**
     * 验证 prompts/list 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // prompts/list 不需要参数
        return true;
    }
}
