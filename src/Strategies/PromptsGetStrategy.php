<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Prompts\PromptsManager;
use Illuminate\Support\Facades\Log;

/**
 * prompts/get 策略
 * 获取特定提示词的详细信息，包括模板内容
 */
class PromptsGetStrategy extends AbstractMcpMethodStrategy
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
        return 'prompts/get';
    }

    /**
     * 处理 prompts/get 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            $name = $params['name'];

            Log::info('MCP prompts/get request', [
                'name' => $name,
            ]);

            // 检查提示词是否存在
            if (!$this->promptsManager->hasPrompt($name)) {
                Log::warning('Prompt not found', [
                    'name' => $name,
                ]);
                return $this->errorResponse($id, -32602, "Prompt not found: {$name}");
            }

            // 获取提示词信息
            $prompt = $this->promptsManager->getPrompt($name);
            $template = $this->promptsManager->getTemplate($name);

            // 构建响应
            $result = [
                'name' => $prompt['name'],
                'description' => $prompt['description'],
            ];

            // 添加 arguments（如果存在）
            if (!empty($prompt['arguments'])) {
                $result['arguments'] = $prompt['arguments'];
            }

            // 添加模板内容
            if ($template !== null) {
                $result['messages'] = [
                    [
                        'role' => 'user',
                        'content' => [
                            'type' => 'text',
                            'text' => $template,
                        ],
                    ],
                ];
            }

            Log::info('Prompt retrieved', [
                'name' => $name,
            ]);

            // 返回成功响应
            return $this->successResponse($id, $result);

        } catch (\Exception $e) {
            Log::error('prompts/get error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'name' => $params['name'] ?? 'unknown',
            ]);

            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    /**
     * 验证 prompts/get 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // 必须包含 name 参数
        if (!isset($params['name']) || !is_string($params['name'])) {
            Log::warning('prompts/get validation failed: missing or invalid name parameter', [
                'params' => $params,
            ]);
            return false;
        }

        // name 不能为空
        if (empty(trim($params['name']))) {
            Log::warning('prompts/get validation failed: empty name');
            return false;
        }

        return true;
    }
}
