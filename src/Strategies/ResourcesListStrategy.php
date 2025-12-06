<?php

namespace JoseChan\McpServer\Strategies;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * resources/list 策略
 * 列出资源目录中的所有文件和文件夹
 */
class ResourcesListStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'resources/list';
    }

    /**
     * 处理 resources/list 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            $resourceDirectory = config('mcp-server.resource_directory');

            Log::info('MCP resources/list request', [
                'directory' => $resourceDirectory,
            ]);

            // 检查目录是否存在
            if (!File::exists($resourceDirectory)) {
                Log::warning('Resource directory does not exist', [
                    'directory' => $resourceDirectory,
                ]);

                // 尝试创建目录
                try {
                    File::makeDirectory($resourceDirectory, 0755, true);
                    Log::info('Created resource directory', [
                        'directory' => $resourceDirectory,
                    ]);
                } catch (\Exception $e) {
                    return $this->errorResponse($id, -32603, 'Resource directory does not exist and cannot be created');
                }
            }

            // 检查是否是目录
            if (!File::isDirectory($resourceDirectory)) {
                Log::error('Resource path is not a directory', [
                    'path' => $resourceDirectory,
                ]);
                return $this->errorResponse($id, -32603, 'Resource path is not a directory');
            }

            // 获取所有文件和文件夹
            $resources = $this->scanDirectory($resourceDirectory);

            // 返回成功响应
            return $this->successResponse($id, [
                'resources' => $resources,
            ]);

        } catch (\Exception $e) {
            Log::error('resources/list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    /**
     * 扫描目录,返回所有文件和文件夹的资源信息
     *
     * @param string $directory
     * @return array
     */
    protected function scanDirectory(string $directory): array
    {
        $resources = [];

        try {
            // 获取目录中的所有项
            $items = File::allFiles($directory);
            $directories = File::directories($directory);

            // 处理文件夹
            foreach ($directories as $dir) {
                $resources[] = $this->buildDirectoryResource($dir);
            }

            // 处理文件
            foreach ($items as $file) {
                $resources[] = $this->buildFileResource($file);
            }

        } catch (\Exception $e) {
            Log::error('Error scanning directory', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
        }

        return $resources;
    }

    /**
     * 构建文件资源信息
     *
     * @param \SplFileInfo $file
     * @return array
     */
    protected function buildFileResource($file): array
    {
        $resourceDirectory = config('mcp-server.resource_directory');

        // 获取相对路径
        $relativePath = str_replace($resourceDirectory . DIRECTORY_SEPARATOR, '', $file->getPathname());

        // 构建资源 URI
        $uri = 'file://' . $relativePath;

        // 检测 MIME 类型
        $mimeType = $this->detectMimeType($file);

        $resource = [
            'uri' => $uri,
            'name' => $file->getFilename(),
            'description' => 'File: ' . $relativePath,
            'mimeType' => $mimeType,
        ];

        // 如果是文本文件,添加文本类型标识
        if (strpos($mimeType, 'text/') === 0) {
            $resource['metadata'] = [
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        return $resource;
    }

    /**
     * 构建文件夹资源信息
     *
     * @param string $directory
     * @return array
     */
    protected function buildDirectoryResource(string $directory): array
    {
        $resourceDirectory = config('mcp-server.resource_directory');

        // 获取相对路径
        $relativePath = str_replace($resourceDirectory . DIRECTORY_SEPARATOR, '', $directory);

        // 构建资源 URI
        $uri = 'file://' . $relativePath . '/';

        return [
            'uri' => $uri,
            'name' => basename($directory),
            'description' => 'Directory: ' . $relativePath,
            'mimeType' => 'inode/directory',
        ];
    }

    /**
     * 检测文件的 MIME 类型
     *
     * @param \SplFileInfo $file
     * @return string
     */
    protected function detectMimeType($file): string
    {
        $extension = strtolower($file->getExtension());

        // 常见文本文件扩展名映射
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
     * 验证 resources/list 请求参数
     * resources/list 可以接受可选的 cursor 参数用于分页
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // resources/list 的参数都是可选的
        // cursor 如果存在,应该是字符串
        if (isset($params['cursor']) && !is_string($params['cursor'])) {
            Log::warning('resources/list validation failed: cursor must be a string', [
                'cursor_type' => gettype($params['cursor']),
            ]);
            return false;
        }

        return true;
    }
}
