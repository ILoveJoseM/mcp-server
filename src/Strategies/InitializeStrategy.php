<?php

namespace JoseChan\McpServer\Strategies;

use Illuminate\Support\Facades\Log;

class InitializeStrategy extends AbstractMcpMethodStrategy
{
    /**
     * 获取该策略处理的方法名
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return 'initialize';
    }

    /**
     * 处理 initialize 请求
     *
     * @param array $params
     * @param mixed $id
     * @return array
     */
    public function handle(array $params, $id): array
    {
        // 获取客户端信息
        $clientInfo = $params['clientInfo'] ?? [];
        $protocolVersion = $params['protocolVersion'] ?? null;

        Log::info('MCP Initialize request', [
            'clientInfo' => $clientInfo,
            'protocolVersion' => $protocolVersion,
        ]);

        // 构建 capabilities
        $capabilities = $this->buildCapabilities();

        // 构建服务器信息
        $serverInfo = [
            'name' => config('mcp-server.server.name'),
            'version' => config('mcp-server.server.version'),
        ];

        // 返回成功响应
        return $this->successResponse($id, [
            'protocolVersion' => config('mcp-server.protocol_version'),
            'capabilities' => $capabilities,
            'serverInfo' => $serverInfo,
        ]);
    }

    /**
     * 验证 initialize 请求参数
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params): bool
    {
        // protocolVersion 是必需的
        if (!isset($params['protocolVersion'])) {
            return false;
        }

        // clientInfo 是必需的
        if (!isset($params['clientInfo'])) {
            return false;
        }

        return true;
    }

    /**
     * 根据配置构建 capabilities
     *
     * @return array
     */
    protected function buildCapabilities(): array
    {
        $config = config('mcp-server.capabilities', []);
        $capabilities = [];

        // Resources capability
        if (!empty($config['resources']['enabled'])) {
            $resourcesCapability = [];

            if (!empty($config['resources']['subscribe'])) {
                $resourcesCapability['subscribe'] = true;
            }

            if (!empty($config['resources']['list_changed'])) {
                $resourcesCapability['listChanged'] = true;
            }

            $capabilities['resources'] = empty($resourcesCapability)
                ? (object)[]
                : $resourcesCapability;
        }

        // Tools capability
        if (!empty($config['tools']['enabled'])) {
            $toolsCapability = [];

            if (!empty($config['tools']['list_changed'])) {
                $toolsCapability['listChanged'] = true;
            }

            if (!empty($config['tools']['call'])) {
                $toolsCapability['call'] = true;
            }

            $capabilities['tools'] = empty($toolsCapability)
                ? (object)[]
                : $toolsCapability;
        }

        // Prompts capability
        if (!empty($config['prompts']['enabled'])) {
            $promptsCapability = [];

            if (!empty($config['prompts']['list_changed'])) {
                $promptsCapability['listChanged'] = true;
            }

            $capabilities['prompts'] = empty($promptsCapability)
                ? (object)[]
                : $promptsCapability;
        }

        // Logging capability
        if (!empty($config['logging']['enabled'])) {
            $capabilities['logging'] = (object)[];
        }

        // Experimental capabilities
        if (!empty($config['experimental']['enabled'])) {
            $capabilities['experimental'] = (object)[];
        }

        return $capabilities;
    }
}
