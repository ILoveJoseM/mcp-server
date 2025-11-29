<?php

namespace JoseChan\McpServer\Providers;

use Illuminate\Support\Facades\Log;
use JoseChan\McpServer\Console\McpServerCommand;
use JoseChan\McpServer\McpMethodStrategyManager;
use JoseChan\McpServer\Prompts\PromptsManager;
use JoseChan\McpServer\Strategies\InitializeStrategy;
use JoseChan\McpServer\Strategies\PingStrategy;
use JoseChan\McpServer\Strategies\PromptsGetStrategy;
use JoseChan\McpServer\Strategies\PromptsListStrategy;
use JoseChan\McpServer\Strategies\ResourcesListStrategy;
use JoseChan\McpServer\Strategies\ResourcesReadStrategy;
use JoseChan\McpServer\Strategies\ResourcesTemplatesListStrategy;
use JoseChan\McpServer\Strategies\ToolsCallStrategy;
use JoseChan\McpServer\Strategies\ToolsListStrategy;
use JoseChan\McpServer\Tools\ToolsManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class McpServerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 合并配置文件
        $this->mergeConfigFrom(
            config_path('mcp.php'), 'mcp'
        );

        // 注册工具管理器为单例
        $this->app->singleton(ToolsManager::class, function ($app) {
            $manager = new ToolsManager();

            // 从配置文件中读取工具类并注册
            $toolClasses = config('mcp.tools', []);
            foreach ($toolClasses as $toolClass) {
                try {
                    $manager->registerToolClass($toolClass);
                } catch (\Exception $e) {
                    Log::error("Failed to register tool class: {$toolClass}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $manager;
        });

        // 注册提示词管理器为单例
        $this->app->singleton(PromptsManager::class, function ($app) {
            $manager = new PromptsManager();

            // 扫描并注册所有提示词
            $manager->scanAndRegister();

            return $manager;
        });

        // 注册策略管理器为单例
        $this->app->singleton(McpMethodStrategyManager::class, function ($app) {
            $manager = new McpMethodStrategyManager();
            $toolsManager = $app->make(ToolsManager::class);
            $promptsManager = $app->make(PromptsManager::class);

            // 注册所有策略
            $manager->registerMultiple([
                new PingStrategy(),
                new InitializeStrategy(),
                new ResourcesListStrategy(),
                new ResourcesTemplatesListStrategy(),
                new ResourcesReadStrategy(),
                new ToolsListStrategy($toolsManager),
                new ToolsCallStrategy($toolsManager),
                new PromptsListStrategy($promptsManager),
                new PromptsGetStrategy($promptsManager),
                // 未来可以在这里添加更多策略
            ]);

            return $manager;
        });

        // 注册命令
        $this->commands([
            McpServerCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../../config/mcp-server.php' => config_path('mcp-server.php'),
        ], 'mcp-server');

        // 注册路由
        $this->registerRoutes();
    }

    /**
     * 注册 MCP 路由
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $config = config('mcp.route', []);

        Route::prefix($config['prefix'] ?? 'mcp')
            ->middleware($config['middleware'] ?? ['api'])
            ->namespace('App\Http\Controllers\Mcp')
            ->group(function () {
                // Initialize 端点 - 使用 POST 方法接收 JSON-RPC 请求
                Route::post('/', 'McpController@initialize');

                // SSE 端点 - 用于实时通信
                Route::get('/sse', 'McpController@sse');
            });
    }
}
