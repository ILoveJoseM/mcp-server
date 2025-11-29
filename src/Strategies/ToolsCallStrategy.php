<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Tools\ToolsManager;
use Illuminate\Support\Facades\Log;

/**
 * tools/call 策略
 * 调用指定的工具
 */
class ToolsCallStrategy extends AbstractMcpMethodStrategy
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
        return 'tools/call';
    }

    /**
     * 处理 tools/call 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        try {
            $toolName = $params['name'];
            $arguments = $params['arguments'] ?? [];

            Log::info('MCP tools/call request', [
                'tool' => $toolName,
                'arguments' => $arguments,
            ]);

            // 检查工具是否存在
            if (!$this->toolsManager->hasTool($toolName)) {
                return $this->successResponse($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Tool not found: {$toolName}",
                        ],
                    ],
                    'isError' => true,
                ]);
            }

            // 调用工具 - 按照参数名称顺序传递参数
            $result = $this->callToolWithNamedArguments($toolName, $arguments);

            // 构建响应内容
            $content = $this->buildResponseContent($result);

            // 返回成功响应
            return $this->successResponse($id, [
                'content' => $content,
                'isError' => false,
            ]);

        } catch (\ArgumentCountError $e) {
            Log::warning('Tool call argument error', [
                'tool' => $params['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return $this->successResponse($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Invalid arguments: " . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Tool call error', [
                'tool' => $params['name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->successResponse($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Error calling tool: " . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ]);
        }
    }

    /**
     * 使用命名参数调用工具
     * 将关联数组参数按照方法签名的顺序转换为位置参数
     *
     * @param string $toolName
     * @param array $namedArguments
     * @return mixed
     * @throws \ReflectionException
     */
    protected function callToolWithNamedArguments(string $toolName, array $namedArguments)
    {
        $toolInfo = $this->toolsManager->getTool($toolName);
        $className = $toolInfo['class'];
        $methodName = $toolInfo['method'];

        // 获取方法的参数信息
        $reflectionMethod = new \ReflectionMethod($className, $methodName);
        $parameters = $reflectionMethod->getParameters();

        // 按照方法签名顺序构建参数数组
        $orderedArguments = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();

            if (array_key_exists($paramName, $namedArguments)) {
                $orderedArguments[] = $namedArguments[$paramName];
            } elseif ($parameter->isOptional()) {
                $orderedArguments[] = $parameter->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(
                    "Missing required parameter: {$paramName}"
                );
            }
        }

        // 调用工具
        return $this->toolsManager->callTool($toolName, $orderedArguments);
    }

    /**
     * 构建响应内容
     *
     * @param mixed $result
     * @return array
     */
    protected function buildResponseContent($result): array
    {
        // 如果结果是数组或对象,转换为 JSON
        if (is_array($result) || is_object($result)) {
            return [
                [
                    'type' => 'text',
                    'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ],
            ];
        }

        // 如果是布尔值,转换为字符串
        if (is_bool($result)) {
            return [
                [
                    'type' => 'text',
                    'text' => $result ? 'true' : 'false',
                ],
            ];
        }

        // 其他类型直接转换为字符串
        return [
            [
                'type' => 'text',
                'text' => (string)$result,
            ],
        ];
    }

    /**
     * 验证 tools/call 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // 必须包含 name 参数
        if (!isset($params['name']) || !is_string($params['name'])) {
            Log::warning('tools/call validation failed: missing or invalid name parameter', [
                'params' => $params,
            ]);
            return false;
        }

        // 工具名称不能为空
        if (empty(trim($params['name']))) {
            Log::warning('tools/call validation failed: empty tool name');
            return false;
        }

        // arguments 如果存在,必须是数组
        if (isset($params['arguments']) && !is_array($params['arguments'])) {
            Log::warning('tools/call validation failed: arguments must be an object', [
                'arguments_type' => gettype($params['arguments']),
            ]);
            return false;
        }

        return true;
    }
}
