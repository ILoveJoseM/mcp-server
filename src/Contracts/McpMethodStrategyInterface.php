<?php

namespace JoseChan\McpServer\Contracts;

interface McpMethodStrategyInterface
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string;

    /**
     * 处理 MCP 方法请求
     *
     * @param array $params 请求参数
     * @param mixed $id JSON-RPC 请求 ID
     * @return array 响应结果
     */
    public function handle(array $params, $id): array;

    /**
     * 验证请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool;
}
