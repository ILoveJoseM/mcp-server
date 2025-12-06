<?php

namespace JoseChan\McpServer\Prompts;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 提示词管理器
 * 负责扫描、注册和管理所有提示词
 */
class PromptsManager
{
    /**
     * 已注册的提示词
     *
     * @var array
     */
    protected $prompts = [];

    /**
     * 提示词目录
     *
     * @var string
     */
    protected $promptsDirectory;

    /**
     * 构造函数
     *
     * @param string|null $promptsDirectory
     */
    public function __construct(?string $promptsDirectory = null)
    {
        $this->promptsDirectory = $promptsDirectory ?? config('mcp-server.prompts_directory', storage_path('mcp_prompts'));
    }

    /**
     * 扫描并注册所有提示词
     *
     * @return void
     */
    public function scanAndRegister(): void
    {
        // 确保目录存在
        if (!File::exists($this->promptsDirectory)) {
            Log::warning('Prompts directory does not exist', [
                'directory' => $this->promptsDirectory,
            ]);
            return;
        }

        // 递归扫描所有 .md 文件
        $files = File::allFiles($this->promptsDirectory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            try {
                $this->registerPromptFile($file->getPathname());
            } catch (\Exception $e) {
                Log::error('Failed to register prompt file', [
                    'file' => $file->getPathname(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Prompts registered', [
            'count' => count($this->prompts),
            'directory' => $this->promptsDirectory,
        ]);
    }

    /**
     * 注册单个提示词文件
     *
     * @param string $filePath
     * @return void
     * @throws \Exception
     */
    protected function registerPromptFile(string $filePath): void
    {
        // 解析 markdown 文件
        $parsed = MarkdownParser::parse($filePath);
        $metadata = $parsed['metadata'];
        $template = $parsed['template'];

        // 验证元数据
        if (!MarkdownParser::validateMetadata($metadata)) {
            Log::warning('Invalid prompt metadata, skipping', [
                'file' => $filePath,
                'metadata' => $metadata,
            ]);
            return;
        }

        // 构建 prompt 对象
        $prompt = MarkdownParser::buildPrompt($metadata, $template);

        // 添加文件路径信息
        $prompt['_file'] = $filePath;
        $prompt['_template'] = $template;

        // 注册
        $this->prompts[$prompt['name']] = $prompt;

        Log::debug('Prompt registered', [
            'name' => $prompt['name'],
            'file' => $filePath,
        ]);
    }

    /**
     * 获取所有提示词
     *
     * @return array
     */
    public function getAllPrompts(): array
    {
        return array_values(array_map(function ($prompt) {
            // 移除内部字段
            $result = [
                'name' => $prompt['name'],
                'description' => $prompt['description'],
            ];

            if (!empty($prompt['arguments'])) {
                $result['arguments'] = $prompt['arguments'];
            }

            return $result;
        }, $this->prompts));
    }

    /**
     * 获取特定提示词
     *
     * @param string $name
     * @return array|null
     */
    public function getPrompt(string $name): ?array
    {
        return $this->prompts[$name] ?? null;
    }

    /**
     * 检查提示词是否存在
     *
     * @param string $name
     * @return bool
     */
    public function hasPrompt(string $name): bool
    {
        return isset($this->prompts[$name]);
    }

    /**
     * 获取提示词的模板内容
     *
     * @param string $name
     * @return string|null
     */
    public function getTemplate(string $name): ?string
    {
        $prompt = $this->getPrompt($name);
        return $prompt['_template'] ?? null;
    }

    /**
     * 获取提示词数量
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->prompts);
    }

    /**
     * 重新加载所有提示词
     *
     * @return void
     */
    public function reload(): void
    {
        $this->prompts = [];
        $this->scanAndRegister();
    }
}
