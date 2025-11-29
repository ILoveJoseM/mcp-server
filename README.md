# Laravel MCP Server

一个用于 Laravel 框架的 MCP (Model Context Protocol) 服务器实现，提供标准化的工具调用、资源管理和提示词功能。

## 功能特性

- 🛠️ **工具管理**: 支持注册和调用自定义工具
- 📁 **资源管理**: 提供资源列表、读取和模板功能
- 💬 **提示词管理**: 支持提示词列表和获取
- 🔧 **灵活配置**: 通过环境变量和配置文件灵活调整
- 🌐 **HTTP 接口**: 提供 RESTful API 和 SSE 支持
- 📝 **标准协议**: 完全兼容 MCP 协议规范 (2024-11-05)

## 系统要求

- PHP >= 7.1.3
- Laravel Framework >= 5.6
- Symfony YAML Component >= 5.3

## 安装说明

### 1. 使用 Composer 安装

```bash
composer require jose-chan/laravel-mcp-server
```

### 2. 发布配置文件

安装完成后，发布配置文件到您的应用中：

```bash
php artisan vendor:publish --provider="JoseChan\McpServer\Providers\McpServerServiceProvider" --tag="mcp-server"
```

这将在 `config/` 目录下创建 `mcp-server.php` 配置文件。

### 3. 配置环境变量

在您的 `.env` 文件中添加以下配置（可选，所有配置项都有默认值）：

```env
# 服务器基本信息
MCP_SERVER_NAME=laravel-mcp-server
MCP_SERVER_VERSION=1.0.0

# 资源和提示词存储目录
MCP_RESOURCE_DIRECTORY=storage/mcp_resources
MCP_PROMPTS_DIRECTORY=storage/mcp_prompts

# 功能开关
MCP_CAPABILITY_RESOURCES=true
MCP_CAPABILITY_RESOURCES_SUBSCRIBE=true
MCP_CAPABILITY_RESOURCES_LIST_CHANGED=true
MCP_CAPABILITY_TOOLS=true
MCP_CAPABILITY_TOOLS_LIST_CHANGED=true
MCP_CAPABILITY_TOOLS_CALL=true
MCP_CAPABILITY_PROMPTS=true
MCP_CAPABILITY_PROMPTS_LIST_CHANGED=true
MCP_CAPABILITY_LOGGING=false
MCP_CAPABILITY_EXPERIMENTAL=false

# SSE 配置
MCP_SSE_TIMEOUT=30
MCP_SSE_RETRY=3000
```

## 配置发布说明

### 配置文件说明

配置文件位于 `config/mcp-server.php`，包含以下主要部分：

#### 1. 服务器信息

```php
'server' => [
    'name' => env('MCP_SERVER_NAME', 'laravel-mcp-server'),
    'version' => env('MCP_SERVER_VERSION', '1.0.0'),
],
```

#### 2. 协议版本

```php
'protocol_version' => '2024-11-05',
```

#### 3. 存储目录

```php
'resource_directory' => env('MCP_RESOURCE_DIRECTORY', storage_path('mcp_resources')),
'prompts_directory' => env('MCP_PROMPTS_DIRECTORY', storage_path('mcp_prompts')),
```

#### 4. 功能能力配置

```php
'capabilities' => [
    'resources' => [
        'enabled' => env('MCP_CAPABILITY_RESOURCES', true),
        'subscribe' => env('MCP_CAPABILITY_RESOURCES_SUBSCRIBE', true),
        'list_changed' => env('MCP_CAPABILITY_RESOURCES_LIST_CHANGED', true),
    ],
    'tools' => [
        'enabled' => env('MCP_CAPABILITY_TOOLS', true),
        'list_changed' => env('MCP_CAPABILITY_TOOLS_LIST_CHANGED', true),
        'call' => env('MCP_CAPABILITY_TOOLS_CALL', true),
    ],
    // ... 更多配置
],
```

#### 5. 路由配置

```php
'route' => [
    'prefix' => 'mcp',
    'middleware' => ['api'],
],
```

#### 6. 工具注册

```php
'tools' => [
    // 在这里添加更多工具类
    // App\\Tools\\MyTool::class,
],
```

## 配置项说明

| 环境变量 | 默认值 | 说明 |
|---------|-------|------|
| MCP_SERVER_NAME | laravel-mcp-server | MCP 服务器名称 |
| MCP_SERVER_VERSION | 1.0.0 | 服务器版本 |
| MCP_RESOURCE_DIRECTORY | storage/mcp_resources | 资源存储目录 |
| MCP_PROMPTS_DIRECTORY | storage/mcp_prompts | 提示词存储目录 |
| MCP_CAPABILITY_RESOURCES | true | 是否启用资源功能 |
| MCP_CAPABILITY_TOOLS | true | 是否启用工具功能 |
| MCP_CAPABILITY_PROMPTS | true | 是否启用提示词功能 |
| MCP_CAPABILITY_LOGGING | false | 是否启用日志功能 |
| MCP_SSE_TIMEOUT | 30 | SSE 超时时间（秒） |
| MCP_SSE_RETRY | 3000 | SSE 重连间隔（毫秒） |

## 使用说明

### 1. 命令行模式

您可以通过STDIO模式启动 MCP 服务器：

```bash
php artisan mcp:server
```

该命令将启动标准输入输出模式的 MCP 服务器，适用于与支持标准输入输出的 MCP 客户端集成。

### 2. HTTP API 模式

服务器也提供 HTTP API 接口，可通过以下端点访问：

- `POST /mcp` - MCP 初始化和请求处理
- `GET /mcp/sse` - Server-Sent Events 实时通信

### 3. 添加自定义工具

1. 创建工具类：

框架将读取所有注册的工具类下的静态方法作为工具返回给MCP-Client调用。
方法的注释将按照标准的PHPDoc格式解析后，提取工具的描述和参数信息传递给MCP-Client

```php
<?php

namespace App\Tools;

class MyTool
{
    /**
     * 工具描述
     * @param array $params 工具参数描述
     */
    public static function exampleFunction($params){
    
    }
}
```

2. 在配置文件中注册工具：

```php
'tools' => [
    App\Tools\MyTool::class,
],
```

### 4. 资源管理

创建资源文件到配置的 `resource_directory` 目录：

```bash
# 创建资源目录
mkdir -p storage/mcp_resources

# 添加资源文件
echo "我的资源内容" > storage/mcp_resources/example.txt
```

### 5. 提示词管理

创建提示词文件到配置的 `prompts_directory` 目录：

```bash
# 创建提示词目录
mkdir -p storage/mcp_prompts

# 添加提示词文件（Markdown 格式）
cat > storage/mcp_prompts/example.md << EOF
---
name: example_prompt
description: 示例提示词
arguments:
  - name: topic
    description: 主题
    required: true
---

请提供一个关于 {{topic}} 的详细说明。
EOF
```

## 示例

### JSON-RPC 请求示例

```json
{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
        "protocolVersion": "2024-11-05",
        "capabilities": {
            "tools": {}
        },
        "clientInfo": {
            "name": "test-client",
            "version": "1.0.0"
        }
    }
}
```

### 工具调用示例

```json
{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
        "name": "my_tool",
        "arguments": {
            "param1": "test value"
        }
    }
}
```

## 故障排除

### 常见问题

1. **工具注册失败**
   - 检查工具类是否正确实现静态方法
   - 确认类路径在配置文件中正确设置

2. **资源无法访问**
   - 确认资源目录存在且有读写权限
   - 检查 `MCP_RESOURCE_DIRECTORY` 环境变量设置

3. **提示词无法加载**
   - 确认提示词目录存在且有读写权限
   - 检查 Markdown 文件格式是否符合要求

### 调试日志

启用日志记录以获取更多信息：

```env
MCP_CAPABILITY_LOGGING=true
```

日志将记录到 Laravel 默认日志系统中。

## 贡献

欢迎提交 Pull Request 和 Issue！

## 许可证

本项目采用 MIT 许可证。详情请参见 [LICENSE](LICENSE) 文件。
