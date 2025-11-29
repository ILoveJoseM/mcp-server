<?php

namespace JoseChan\McpServer;

use JoseChan\McpServer\Exceptions\McpServerException;

class McpServerHandler
{
    private $strategyManager;

    public function __construct(McpMethodStrategyManager $strategyManager)
    {
        $this->strategyManager = $strategyManager;
    }

    /**
     * 处理 MCP JSON-RPC 请求
     * @param $jsonRpc
     * @return array
     * @throws McpServerException
     */
    public function handleMcpRequest($jsonRpc){
        // 验证 JSON-RPC 格式
        $validationResult = $this->validateJsonRpcRequest($jsonRpc);
        if(!$validationResult){
            throw new McpServerException('Invalid JSON-RPC request', -32601);
        }

        // 提取请求信息
        $method = $jsonRpc['method'];
        $params = $jsonRpc['params'] ?? [];
        $id = $jsonRpc['id'] ?? null;

        // 检查方法是否存在
        if (!$this->strategyManager->hasStrategy($method)) {
            throw new McpServerException('Method not found', -32601);
        }

        // 获取策略并验证参数
        $strategy = $this->strategyManager->getStrategy($method);

        if (!$strategy->validate($params)) {
            throw new McpServerException('Invalid params', -32602);
        }

        // 执行策略处理
        return $strategy->handle($params, $id);
    }
    /**
     * 构建错误响应
     *
     * @param mixed $id
     * @param int $code
     * @param string $message
     * @param mixed|null $data
     * @return array
     */
    public function buildErrorResponse($id, int $code, string $message, $data = null): array
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $error['data'] = $data;
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => $error,
        ];
    }

    /**
     * 验证 JSON-RPC 请求格式
     *
     * @param array $jsonRpc
     * @return bool 如果验证通过返回 true,否则返回错误响应数组
     */
    public function validateJsonRpcRequest(?array $jsonRpc)
    {
        if(empty($jsonRpc)){
            throw new McpServerException('Invalid Request: empty request', -32600);
        }

        // 验证 jsonrpc 版本
        if (!isset($jsonRpc['jsonrpc']) || $jsonRpc['jsonrpc'] !== '2.0') {
            throw new McpServerException('Invalid Request: missing or invalid jsonrpc version', -32600);
        }

        // 验证 method 字段
        if (!isset($jsonRpc['method']) || !is_string($jsonRpc['method'])) {
            throw new McpServerException('Invalid Request: missing or invalid method', -32600);
        }

        return true;
    }
}
