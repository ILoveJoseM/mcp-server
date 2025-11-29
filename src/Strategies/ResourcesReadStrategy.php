<?php

namespace JoseChan\McpServer\Strategies;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * resources/read 策略
 * 读取指定的资源内容
 */
class ResourcesReadStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'resources/read';
    }

    /**
     * 处理 resources/read 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            $uri = $params['uri'];

            Log::info('MCP resources/read request', [
                'uri' => $uri,
            ]);

            // 解析 URI
            $filePath = $this->parseUri($uri);

            if ($filePath === null) {
                return $this->errorResponse($id, -32602, 'Invalid URI format');
            }

            // 构建完整路径
            $resourceDirectory = config('mcp.resource_directory');
            $fullPath = $resourceDirectory . DIRECTORY_SEPARATOR . $filePath;

            // 安全检查: 确保路径在资源目录内
            $realPath = realpath($fullPath);
            $realResourceDir = realpath($resourceDirectory);

            if (!$realPath || strpos($realPath, $realResourceDir) !== 0) {
                Log::warning('Attempt to access file outside resource directory', [
                    'uri' => $uri,
                    'attempted_path' => $fullPath,
                ]);
                return $this->errorResponse($id, -32602, 'Access denied: path outside resource directory');
            }

            // 检查文件是否存在
            if (!File::exists($realPath)) {
                Log::warning('Resource file not found', [
                    'uri' => $uri,
                    'path' => $realPath,
                ]);
                return $this->errorResponse($id, -32602, 'Resource not found');
            }

            // 检查是否是文件
            if (!File::isFile($realPath)) {
                Log::warning('Resource is not a file', [
                    'uri' => $uri,
                    'path' => $realPath,
                ]);
                return $this->errorResponse($id, -32602, 'Resource is not a file');
            }

            // 读取文件内容
            $content = File::get($realPath);
            $mimeType = $this->detectMimeType($realPath);

            // 构建响应
            $contents = [
                [
                    'uri' => $uri,
                    'mimeType' => $mimeType,
                ]
            ];

            // 根据 MIME 类型添加内容
            if (strpos($mimeType, 'text/') === 0 || in_array($mimeType, ['application/json', 'application/xml'])) {
                $contents[0]['text'] = $content;
            } else {
                // 二进制文件使用 base64 编码
                $contents[0]['blob'] = base64_encode($content);
            }

            // 返回成功响应
            return $this->successResponse($id, [
                'contents' => $contents,
            ]);

        } catch (\Exception $e) {
            Log::error('resources/read error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'uri' => $params['uri'] ?? 'unknown',
            ]);

            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    /**
     * 解析 URI,提取文件路径
     *
     * @param string $uri
     * @return string|null
     */
    protected function parseUri(string $uri): ?string
    {
        // 支持的 URI 格式: file://path/to/file 或 file:///path/to/file
        if (strpos($uri, 'file://') === 0) {
            $path = substr($uri, 7); // 移除 'file://'
            // 移除开头的斜杠
            $path = ltrim($path, '/');
            return $path;
        }

        return null;
    }

    /**
     * 检测文件的 MIME 类型
     *
     * @param string $filePath
     * @return string
     */
    protected function detectMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 常见文件类型映射
        $mimeTypes = [
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'text/javascript',
            'php' => 'text/x-php',
            'py' => 'text/x-python',
            'java' => 'text/x-java',
            'c' => 'text/x-c',
            'cpp' => 'text/x-c++',
            'h' => 'text/x-c',
            'sh' => 'text/x-shellscript',
            'sql' => 'text/x-sql',
            'log' => 'text/plain',
            'csv' => 'text/csv',
            'yaml' => 'text/yaml',
            'yml' => 'text/yaml',
            'ini' => 'text/plain',
            'conf' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * 验证 resources/read 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // 必须包含 uri 参数
        if (!isset($params['uri']) || !is_string($params['uri'])) {
            Log::warning('resources/read validation failed: missing or invalid uri parameter', [
                'params' => $params,
            ]);
            return false;
        }

        // URI 不能为空
        if (empty(trim($params['uri']))) {
            Log::warning('resources/read validation failed: empty uri');
            return false;
        }

        // URI 必须以 file:// 开头
        if (strpos($params['uri'], 'file://') !== 0) {
            Log::warning('resources/read validation failed: uri must start with file://', [
                'uri' => $params['uri'],
            ]);
            return false;
        }

        return true;
    }
}
