<?php

namespace JoseChan\McpServer\Tools;

use ReflectionMethod;

/**
 * PHPDoc 注释解析器
 * 用于从方法注释中提取工具描述和参数信息
 */
class DocBlockParser
{
    /**
     * 解析方法的 PHPDoc 注释
     *
     * @param ReflectionMethod $method
     * @return array
     */
    public static function parse(ReflectionMethod $method): array
    {
        $docComment = $method->getDocComment();

        if (!$docComment) {
            return [
                'description' => '',
                'parameters' => [],
                'return' => null,
            ];
        }

        return [
            'description' => self::extractDescription($docComment),
            'parameters' => self::extractParameters($docComment, $method),
            'return' => self::extractReturn($docComment),
        ];
    }

    /**
     * 提取描述信息
     *
     * @param string $docComment
     * @return string
     */
    protected static function extractDescription(string $docComment): string
    {
        // 移除开始和结束的注释标记
        $lines = explode("\n", $docComment);
        $description = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // 移除 /** 和 */
            $line = preg_replace('/^\/\*\*\s*/', '', $line);
            $line = preg_replace('/\s*\*\/$/', '', $line);
            // 移除行首的 *
            $line = preg_replace('/^\*\s*/', '', $line);

            // 如果遇到 @ 开头的标签，说明描述部分结束
            if (strpos($line, '@') === 0) {
                break;
            }

            if (!empty($line)) {
                $description[] = $line;
            }
        }

        return implode(' ', $description);
    }

    /**
     * 提取参数信息
     *
     * @param string $docComment
     * @param ReflectionMethod $method
     * @return array
     */
    protected static function extractParameters(string $docComment, ReflectionMethod $method): array
    {
        $parameters = [];
        $paramTags = self::extractTags($docComment, 'param');
        $reflectionParams = $method->getParameters();

        foreach ($reflectionParams as $reflectionParam) {
            $paramName = $reflectionParam->getName();
            $paramInfo = [
                'name' => $paramName,
                'type' => self::getParameterType($reflectionParam),
                'description' => '',
                'required' => !$reflectionParam->isOptional(),
            ];

            // 从注释中查找参数描述
            foreach ($paramTags as $tag) {
                if (preg_match('/@param\s+(\S+)\s+\$' . $paramName . '\s+(.+)/', $tag, $matches)) {
                    $paramInfo['description'] = trim($matches[2]);
                    // 如果反射没有获取到类型，从注释中获取
                    if ($paramInfo['type'] === 'mixed') {
                        $paramInfo['type'] = self::normalizeType($matches[1]);
                    }
                    break;
                }
            }

            $parameters[] = $paramInfo;
        }

        return $parameters;
    }

    /**
     * 提取返回值信息
     *
     * @param string $docComment
     * @return array|null
     */
    protected static function extractReturn(string $docComment): ?array
    {
        $returnTags = self::extractTags($docComment, 'return');

        if (empty($returnTags)) {
            return null;
        }

        $tag = $returnTags[0];
        if (preg_match('/@return\s+(\S+)(?:\s+(.+))?/', $tag, $matches)) {
            return [
                'type' => self::normalizeType($matches[1]),
                'description' => isset($matches[2]) ? trim($matches[2]) : '',
            ];
        }

        return null;
    }

    /**
     * 提取指定标签的所有内容
     *
     * @param string $docComment
     * @param string $tagName
     * @return array
     */
    protected static function extractTags(string $docComment, string $tagName): array
    {
        $tags = [];
        $lines = explode("\n", $docComment);

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\*\s*/', '', $line);

            if (strpos($line, '@' . $tagName) === 0) {
                $tags[] = $line;
            }
        }

        return $tags;
    }

    /**
     * 获取参数类型
     *
     * @param \ReflectionParameter $param
     * @return string
     */
    protected static function getParameterType(\ReflectionParameter $param): string
    {
        if ($param->hasType()) {
            $type = $param->getType();
            if (method_exists($type, 'getName')) {
                return self::normalizeType($type->getName());
            }
            return self::normalizeType((string)$type);
        }

        return 'mixed';
    }

    /**
     * 标准化类型名称，映射到 JSON Schema 类型
     *
     * @param string $type
     * @return string
     */
    protected static function normalizeType(string $type): string
    {
        $typeMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'mixed' => 'string', // 默认为 string
        ];

        $type = strtolower(trim($type));
        return $typeMap[$type] ?? 'string';
    }
}
