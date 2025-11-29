<?php

namespace JoseChan\McpServer\Console;

use JoseChan\McpServer\Exceptions\McpServerException;
use JoseChan\McpServer\McpServerHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class McpServerCommand extends Command
{
    protected $name = "mcp:server";

    protected $description = "MCP服务器";

    protected $stdin;
    protected $stdout;
    protected $stderr;

    public function handle()
    {
        // 打开标准输入输出
        $this->stdin = STDIN;
        $this->stdout = STDOUT;
        $this->stderr = STDERR;

        // 开启循环读取标注输入中的每一行，直到EOF
        while (!feof($this->stdin)) {
            $jsonRpc = $this->getJsonRpcInput();
            if(empty($jsonRpc)){
                continue;
            }
            $result = $this->getJsonpOutput($jsonRpc);

            if(empty($result['result'])){
                $options = 336;
            } else {
                $options = 320;
            }
            $jsonResponse = json_encode($result, $options);
            $this->output($jsonResponse);
            usleep(100);
        }
    }

    public function getJsonpOutput($jsonRpc)
    {
        /** @var McpServerHandler $mcpServerHandler */
        $mcpServerHandler = app(McpServerHandler::class);

        try {
            return $mcpServerHandler->handleMcpRequest($jsonRpc);
        } catch (McpServerException|\InvalidArgumentException $e){
            Log::warning('MCP server exception', [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
                'request' => $jsonRpc
            ]);

            return $mcpServerHandler->buildErrorResponse(null, $e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            return $mcpServerHandler->buildErrorResponse(null, -32603, 'Internal error');
        }
    }

    /**
     * @return mixed
     */
    public function getJsonRpcInput()
    {
        try {
            $input = fgets($this->stdin);

            // 跳过空行
            if ($input === false || trim($input) === '') {
                return null;
            }

            $decoded = json_decode($input, true);

            // 验证 JSON 解析是否成功
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to decode JSON', [
                    'input' => $input,
                    'error' => json_last_error_msg()
                ]);
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('Exception in getJsonRpcInput', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @param $jsonResponse
     * @return void
     */
    public function output($jsonResponse): void
    {
        // 关键:每条 JSON-RPC 消息必须以换行符结尾
        fwrite($this->stdout, $jsonResponse . "\n");

        // 确保立即刷新输出缓冲区
        fflush($this->stdout);

        // 日志记录(确保你的日志不会输出到 stderr)
        Log::info("mcp.response.log",[
            'response' => $jsonResponse,
        ]);
    }
}
