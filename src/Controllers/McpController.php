<?php

namespace JoseChan\McpServer\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use JoseChan\McpServer\Exceptions\McpServerException;
use JoseChan\McpServer\McpServerHandler;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpController extends Controller
{
    protected $mcpServerHandler;

    /**
     * 构造函数
     *
     * @param McpServerHandler $mcpServerHandler
     */
    public function __construct(McpServerHandler $mcpServerHandler)
    {
        $this->mcpServerHandler = $mcpServerHandler;
    }

    /**
     * 处理 MCP JSON-RPC 请求
     *
     * MCP 协议使用 JSON-RPC 2.0 格式
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initialize(Request $request): JsonResponse
    {
        try {
            // 解析 JSON-RPC 请求
            $jsonRpc = $request->json()->all();

            $result = $this->mcpServerHandler->handleMcpRequest($jsonRpc);

            if(empty($result['result'])){
                $options = 336;
            } else {
                $options = 320;
            }
            return response()->json($result, 200, [], $options);

        } catch (McpServerException|\InvalidArgumentException $e) {
            Log::warning('MCP method not found', [
                'exception' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(
                $this->mcpServerHandler->buildErrorResponse(null, $e->getCode(), $e->getMessage()),
                400
            );

        } catch (\Exception $e) {
            Log::error('MCP request error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(
                $this->mcpServerHandler->buildErrorResponse(null, -32603, 'Internal error'),
                500
            );
        }
    }

    /**
     * SSE 端点 - 用于实时通信
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function sse(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            // 设置 SSE 头部
            echo "retry: " . config('mcp-server.sse.retry', 3000) . "\n\n";

            // 发送连接成功消息
            echo "event: connected\n";
            echo "data: " . json_encode([
                'type' => 'connected',
                'timestamp' => time(),
            ]) . "\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            // 保持连接
            $timeout = config('mcp-server.sse.timeout', 30);
            $startTime = time();

            while (time() - $startTime < $timeout) {
                // 发送心跳
                echo "event: ping\n";
                echo "data: " . json_encode(['timestamp' => time()]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(10);

                // 检查连接是否断开
                if (connection_aborted()) {
                    break;
                }
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
