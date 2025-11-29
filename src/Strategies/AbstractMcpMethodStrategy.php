<?php

namespace JoseChan\McpServer\Strategies;

use JoseChan\McpServer\Contracts\McpMethodStrategyInterface;

abstract class AbstractMcpMethodStrategy implements McpMethodStrategyInterface
{
    /**
     * 构建成功响应
     *
     * @param mixed $id
     * @param array $result
     * @return array
     */
    protected function successResponse($id, array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
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
    protected function errorResponse($id, int $code, string $message, $data = null): array
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
     * 默认验证实现
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        return true;
    }
}
