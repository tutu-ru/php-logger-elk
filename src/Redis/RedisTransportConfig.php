<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\Redis;

use TutuRu\Config\ConfigContainer;
use TutuRu\LoggerElk\ElkLogger;

class RedisTransportConfig
{
    public const METRIC_PREFIX = ElkLogger::METRIC_PREFIX;

    /** @var ConfigContainer */
    private $config;


    public function __construct(ConfigContainer $config)
    {
        $this->config = $config;
    }


    public function getConnectionNames(): array
    {
        return (array)$this->config->getValue('logstash.redis.connections.name', []);
    }


    public function getRetryTimeoutForRedisInSec(): int
    {
        return (int)$this->config->getValue('logstash.retry_timeout_for_redis_in_sec', 3);
    }
}
