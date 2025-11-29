<?php

namespace JoseChan\McpServer\Tools;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Facades\Log;

/**
 * 工具管理器
 * 负责注册和管理所有 MCP 工具
 */
class ToolsManager
{
    /**
     * 已注册的工具列表
     * 格式: ['tool_name' => ['class' => ClassName, 'method' => 'methodName', 'schema' => [...]]]
     *
     * @var array
     */
    protected $tools = [];

    /**
     * 注册工具类
     * 扫描类中所有 public static 方法并注册为工具
     *
     * @param string $className 工具类的完全限定名
     * @return void
     * @throws \ReflectionException
     */
    public function registerToolClass(string $className): void
    {
        if (!class_exists($className)) {
            Log::warning("Tool class not found: {$className}");
            return;
        }

        $reflection = new ReflectionClass($className);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            // 跳过魔术方法和继承的方法
            if (strpos($method->getName(), '__') === 0 || $method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $this->registerToolMethod($className, $method);
        }
    }

    /**
     * 注册单个工具方法
     *
     * @param string $className
     * @param ReflectionMethod $method
     * @return void
     */
    protected function registerToolMethod(string $className, ReflectionMethod $method): void
    {
        $methodName = $method->getName();

        // 工具名称格式: ClassName_methodName
        $toolName = $this->generateToolName($className, $methodName);

        // 解析方法注释
        $docInfo = DocBlockParser::parse($method);

        // 构建工具 schema
        $schema = $this->buildToolSchema($toolName, $docInfo);

        $this->tools[$toolName] = [
            'class' => $className,
            'method' => $methodName,
            'schema' => $schema,
            'return' => $docInfo['return'],
        ];

        Log::info("Registered tool: {$toolName}", ['class' => $className, 'method' => $methodName]);
    }

    /**
     * 生成工具名称
     *
     * @param string $className
     * @param string $methodName
     * @return string
     */
    protected function generateToolName(string $className, string $methodName): string
    {
        // 获取类的短名称 (不含命名空间)
        $parts = explode('\\', $className);
        $shortClassName = end($parts);

        // 移除 "Tools" 后缀
        $shortClassName = preg_replace('/Tools$/', '', $shortClassName);

        // 转换为 snake_case
        $toolPrefix = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortClassName));
        $toolMethod = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $methodName));

        return $toolPrefix . '_' . $toolMethod;
    }

    /**
     * 构建工具的 JSON Schema
     *
     * @param string $toolName
     * @param array $docInfo
     * @return array
     */
    protected function buildToolSchema(string $toolName, array $docInfo): array
    {
        $schema = [
            'name' => $toolName,
            'description' => $docInfo['description'] ?: "Tool: {$toolName}",
        ];

        // 构建输入参数 schema
        if (!empty($docInfo['parameters'])) {
            $inputSchema = [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];

            foreach ($docInfo['parameters'] as $param) {
                $inputSchema['properties'][$param['name']] = [
                    'type' => $param['type'],
                    'description' => $param['description'],
                ];

                if ($param['required']) {
                    $inputSchema['required'][] = $param['name'];
                }
            }

            // 如果没有必需参数,移除 required 字段
            if (empty($inputSchema['required'])) {
                unset($inputSchema['required']);
            }

            $schema['inputSchema'] = $inputSchema;
        }

        return $schema;
    }

    /**
     * 获取所有已注册的工具
     *
     * @return array
     */
    public function getAllTools(): array
    {
        $tools = [];

        foreach ($this->tools as $toolName => $toolInfo) {
            $tools[] = $toolInfo['schema'];
        }

        return $tools;
    }

    /**
     * 获取指定工具的信息
     *
     * @param string $toolName
     * @return array|null
     */
    public function getTool(string $toolName): ?array
    {
        return $this->tools[$toolName] ?? null;
    }

    /**
     * 检查工具是否存在
     *
     * @param string $toolName
     * @return bool
     */
    public function hasTool(string $toolName): bool
    {
        return isset($this->tools[$toolName]);
    }

    /**
     * 调用工具
     *
     * @param string $toolName
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function callTool(string $toolName, array $arguments)
    {
        if (!$this->hasTool($toolName)) {
            throw new \Exception("Tool not found: {$toolName}");
        }

        $toolInfo = $this->tools[$toolName];
        $className = $toolInfo['class'];
        $methodName = $toolInfo['method'];

        try {
            // 调用静态方法
            return call_user_func_array([$className, $methodName], $arguments);
        } catch (\Exception $e) {
            Log::error("Error calling tool {$toolName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取已注册工具的数量
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->tools);
    }
}
