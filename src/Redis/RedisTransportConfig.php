<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\Redis;

use TutuRu\Config\ConfigInterface;

class RedisTransportConfig
{
    /** @var ConfigInterface */
    private $config;


    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }


    public function getListName(): string
    {
        return 'logs-list';
    }


    public function getConnectionNames(): array
    {
        return (array)$this->config->getValue('logstash.redis.connections.name', false, []);
    }


    public function getRetryTimeoutForRedisInSec(): int
    {
        return (int)$this->config->getValue('logstash.retry_timeout_for_redis_in_sec', false, 3);
    }
}
