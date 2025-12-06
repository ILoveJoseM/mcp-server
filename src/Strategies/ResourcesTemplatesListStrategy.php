<?php

namespace JoseChan\McpServer\Strategies;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * resources/templates/list 策略
 * 列出所有资源模板
 */
class ResourcesTemplatesListStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'resources/templates/list';
    }

    /**
     * 处理 resources/templates/list 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            $resourceDirectory = config('mcp-server.resource_directory');

            Log::info('MCP resources/templates/list request');

            // 检查目录是否存在
            if (!File::exists($resourceDirectory)) {
                Log::warning('Resource directory does not exist', [
                    'directory' => $resourceDirectory,
                ]);
                return $this->successResponse($id, [
                    'resourceTemplates' => [],
                ]);
            }

            // 获取所有资源模板
            $templates = $this->buildResourceTemplates($resourceDirectory);

            // 返回成功响应
            return $this->successResponse($id, [
                'resourceTemplates' => $templates,
            ]);

        } catch (\Exception $e) {
            Log::error('resources/templates/list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    /**
     * 构建资源模板列表
     *
     * @param string $directory
     * @return array
     */
    protected function buildResourceTemplates(string $directory): array
    {
        $templates = [];

        try {
            // 模板1: 列出所有文件
            $templates[] = [
                'uriTemplate' => 'file:///{path}',
                'name' => 'File Resource',
                'description' => 'Access any file in the resource directory by path',
                'mimeType' => 'application/octet-stream',
            ];

            // 模板2: 列出文本文件
            $templates[] = [
                'uriTemplate' => 'file:///{path}.txt',
                'name' => 'Text File',
                'description' => 'Access text files in the resource directory',
                'mimeType' => 'text/plain',
            ];

            // 模板3: 列出 JSON 文件
            $templates[] = [
                'uriTemplate' => 'file:///{path}.json',
                'name' => 'JSON File',
                'description' => 'Access JSON files in the resource directory',
                'mimeType' => 'application/json',
            ];

            // 模板4: 列出 Markdown 文件
            $templates[] = [
                'uriTemplate' => 'file:///{path}.md',
                'name' => 'Markdown File',
                'description' => 'Access Markdown files in the resource directory',
                'mimeType' => 'text/markdown',
            ];

        } catch (\Exception $e) {
            Log::error('Error building resource templates', [
                'error' => $e->getMessage(),
            ]);
        }

        return $templates;
    }

    /**
     * 验证 resources/templates/list 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // resources/templates/list 不需要参数
        return true;
    }
}
