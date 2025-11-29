<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Information
    |--------------------------------------------------------------------------
    |
    | 配置 MCP 服务器的基本信息
    |
    */
    'server' => [
        'name' => env('MCP_SERVER_NAME', 'laravel-mcp-server'),
        'version' => env('MCP_SERVER_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Protocol Version
    |--------------------------------------------------------------------------
    |
    | MCP 协议版本
    |
    */
    'protocol_version' => '2024-11-05',

    'resource_directory' => env('MCP_RESOURCE_DIRECTORY', storage_path('mcp_resources')),

    'prompts_directory' => env('MCP_PROMPTS_DIRECTORY', storage_path('mcp_prompts')),

    /*
    |--------------------------------------------------------------------------
    | Server Capabilities
    |--------------------------------------------------------------------------
    |
    | 配置 MCP 服务器支持的功能
    | 可选的 capabilities: resources, tools, prompts, logging
    |
    */
    'capabilities' => [
        // 是否支持 resources 功能
        'resources' => [
            'enabled' => env('MCP_CAPABILITY_RESOURCES', true),
            'subscribe' => env('MCP_CAPABILITY_RESOURCES_SUBSCRIBE', true),
            'list_changed' => env('MCP_CAPABILITY_RESOURCES_LIST_CHANGED', true),
        ],

        // 是否支持 tools 功能
        'tools' => [
            'enabled' => env('MCP_CAPABILITY_TOOLS', true),
            'list_changed' => env('MCP_CAPABILITY_TOOLS_LIST_CHANGED', true),
            'call' => env('MCP_CAPABILITY_TOOLS_CALL', true),
        ],

        // 是否支持 prompts 功能
        'prompts' => [
            'enabled' => env('MCP_CAPABILITY_PROMPTS', true),
            'list_changed' => env('MCP_CAPABILITY_PROMPTS_LIST_CHANGED', true),
        ],

        // 是否支持 logging 功能
        'logging' => [
            'enabled' => env('MCP_CAPABILITY_LOGGING', false),
        ],

        // 是否支持 experimental capabilities
        'experimental' => [
            'enabled' => env('MCP_CAPABILITY_EXPERIMENTAL', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | 路由配置
    |
    */
    'route' => [
        'prefix' => 'mcp',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Configuration
    |--------------------------------------------------------------------------
    |
    | 注册工具类
    | 数组中的每个类的所有 public static 方法都会被注册为工具
    |
    */
    'tools' => [
        // 在这里添加更多工具类
    ],

    /*
    |--------------------------------------------------------------------------
    | SSE Configuration
    |--------------------------------------------------------------------------
    |
    | Server-Sent Events 配置
    |
    */
    'sse' => [
        'timeout' => env('MCP_SSE_TIMEOUT', 30),
        'retry' => env('MCP_SSE_RETRY', 3000), // 重连间隔,单位毫秒
    ],
];
