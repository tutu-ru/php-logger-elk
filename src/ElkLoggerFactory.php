<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use TutuRu\Config\ConfigInterface;
use TutuRu\Config\EnvironmentUtils;
use TutuRu\LoggerElk\ErrorLog\ErrorLogMessageProcessor;
use TutuRu\LoggerElk\ErrorLog\ErrorLogTransport;
use TutuRu\LoggerElk\Redis\RedisMessageProcessor;
use TutuRu\LoggerElk\Redis\RedisTransport;
use TutuRu\LoggerElk\Redis\RedisTransportConfig;
use TutuRu\Metrics\MetricAwareInterface;
use TutuRu\Metrics\StatsdExporterClientInterface;
use TutuRu\Redis\ConnectionManager;
use TutuRu\Redis\RedisPushListInterface;
use TutuRu\RequestMetadata\RequestMetadata;

class ElkLoggerFactory
{
    /** @var HostnameBasedEnvDataProvider */
    private $envDataProvider;


    public function __construct()
    {
        $this->envDataProvider = new HostnameBasedEnvDataProvider(EnvironmentUtils::getServerHostname());
        $this->envDataProvider->generateHash();
    }


    public function getNativeErrorLogger($log, ?RequestMetadata $requestMetadata = null)
    {
        $transport = new ErrorLogTransport();
        $messageProcessor = new ErrorLogMessageProcessor($this->envDataProvider, $requestMetadata);
        return new ElkLogger($log, $transport, $messageProcessor);
    }


    public function getRedisLogger(
        string $log,
        ConfigInterface $config,
        ConnectionManager $connectionManager,
        ?RequestMetadata $requestMetadata = null,
        ?StatsdExporterClientInterface $statsdExporterClient = null
    ) {
        $config = new RedisTransportConfig($config);
        $listGroup = $this->getRedisListGroup($connectionManager, $config);
        if (!is_null($statsdExporterClient) && $listGroup instanceof MetricAwareInterface) {
            $listGroup->setStatsdExporterClient($statsdExporterClient);
        }

        $transport = new RedisTransport($listGroup);
        $messageProcessor = new RedisMessageProcessor($this->envDataProvider, $requestMetadata);

        $logger = new ElkLogger($log, $transport, $messageProcessor);
        if (!is_null($statsdExporterClient)) {
            $logger->setStatsdExporterClient($statsdExporterClient);
        }
        return $logger;
    }


    private function getRedisListGroup(
        ConnectionManager $connectionManager,
        RedisTransportConfig $config
    ): RedisPushListInterface {
        $listGroup = $connectionManager->createHaPushListGroup($config->getListName(), $config->getConnectionNames());
        $listGroup->setRetryTimeout($config->getRetryTimeoutForRedisInSec());
        $listGroup->setGroupName('log_elk_redis');
        return $listGroup;
    }
}
