<?php

namespace JoseChan\McpServer;

use JoseChan\McpServer\Contracts\McpMethodStrategyInterface;
use InvalidArgumentException;

class McpMethodStrategyManager
{
    /**
     * 策略映射表
     *
     * @var array<string, McpMethodStrategyInterface>
     */
    protected $strategies = [];

    /**
     * 注册策略
     *
     * @param McpMethodStrategyInterface $strategy
     * @return void
     */
    public function register(McpMethodStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getMethodName()] = $strategy;
    }

    /**
     * 批量注册策略
     *
     * @param array<McpMethodStrategyInterface> $strategies
     * @return void
     */
    public function registerMultiple(array $strategies): void
    {
        foreach ($strategies as $strategy) {
            $this->register($strategy);
        }
    }

    /**
     * 获取策略
     *
     * @param string $method
     * @return McpMethodStrategyInterface
     * @throws InvalidArgumentException
     */
    public function getStrategy(string $method): McpMethodStrategyInterface
    {
        if (!isset($this->strategies[$method])) {
            throw new InvalidArgumentException("Method '{$method}' not found");
        }

        return $this->strategies[$method];
    }

    /**
     * 检查方法是否存在
     *
     * @param string $method
     * @return bool
     */
    public function hasStrategy(string $method): bool
    {
        return isset($this->strategies[$method]);
    }

    /**
     * 获取所有已注册的方法名
     *
     * @return array<string>
     */
    public function getRegisteredMethods(): array
    {
        return array_keys($this->strategies);
    }
}
