<?php

namespace JoseChan\McpServer\Prompts;

use Symfony\Component\Yaml\Yaml;

/**
 * Markdown 文件解析器
 * 支持解析 YAML frontmatter 和 markdown 内容
 */
class MarkdownParser
{
    /**
     * 解析 markdown 文件
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public static function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Markdown file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);

        // 解析 frontmatter 和 body
        $parts = self::parseFrontmatter($content);

        return [
            'metadata' => $parts['frontmatter'],
            'template' => $parts['body'],
        ];
    }

    /**
     * 解析 frontmatter
     *
     * @param string $content
     * @return array ['frontmatter' => array, 'body' => string]
     */
    protected static function parseFrontmatter(string $content): array
    {
        // 匹配 YAML frontmatter: ---\n内容\n---
        $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)$/s';

        if (preg_match($pattern, $content, $matches)) {
            try {
                $frontmatter = Yaml::parse($matches[1]);
                $body = trim($matches[2]);

                return [
                    'frontmatter' => is_array($frontmatter) ? $frontmatter : [],
                    'body' => $body,
                ];
            } catch (\Exception $e) {
                // YAML 解析失败，返回空 frontmatter
                return [
                    'frontmatter' => [],
                    'body' => $content,
                ];
            }
        }

        // 没有 frontmatter
        return [
            'frontmatter' => [],
            'body' => $content,
        ];
    }

    /**
     * 验证 frontmatter 的必需字段
     *
     * @param array $metadata
     * @return bool
     */
    public static function validateMetadata(array $metadata): bool
    {
        // 必须包含 name 和 description
        if (empty($metadata['name']) || empty($metadata['description'])) {
            return false;
        }

        // 如果有 arguments，必须是数组
        if (isset($metadata['arguments']) && !is_array($metadata['arguments'])) {
            return false;
        }

        return true;
    }

    /**
     * 从 frontmatter 构建 prompt 对象
     *
     * @param array $metadata
     * @param string $template
     * @return array
     */
    public static function buildPrompt(array $metadata, string $template): array
    {
        $prompt = [
            'name' => $metadata['name'],
            'description' => $metadata['description'] ?? '',
        ];

        // 添加 arguments（如果存在）
        if (!empty($metadata['arguments']) && is_array($metadata['arguments'])) {
            $prompt['arguments'] = self::normalizeArguments($metadata['arguments']);
        }

        return $prompt;
    }

    /**
     * 规范化 arguments 格式
     *
     * @param array $arguments
     * @return array
     */
    protected static function normalizeArguments(array $arguments): array
    {
        $normalized = [];

        foreach ($arguments as $arg) {
            if (!is_array($arg) || empty($arg['name'])) {
                continue;
            }

            $normalized[] = [
                'name' => $arg['name'],
                'description' => $arg['description'] ?? '',
                'required' => $arg['required'] ?? false,
            ];
        }

        return $normalized;
    }
}
