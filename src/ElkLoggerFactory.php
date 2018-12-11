<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use TutuRu\Config\ConfigContainer;
use TutuRu\Config\EnvironmentUtils;
use TutuRu\LoggerElk\ErrorLog\ErrorLogMessageProcessor;
use TutuRu\LoggerElk\ErrorLog\ErrorLogTransport;
use TutuRu\LoggerElk\Redis\RedisMessageProcessor;
use TutuRu\LoggerElk\Redis\RedisTransport;
use TutuRu\LoggerElk\Redis\RedisTransportConfig;
use TutuRu\Metrics\SessionRegistryInterface;
use TutuRu\Redis\ConnectionManager;
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


    public function getRedisLogger(
        string $log,
        ConfigContainer $config,
        ConnectionManager $connectionManager,
        ?RequestMetadata $requestMetadata = null,
        ?SessionRegistryInterface $metricsSessionRegistry = null
    ) {
        $transport = new RedisTransport(new RedisTransportConfig($config), $connectionManager);
        if (!is_null($metricsSessionRegistry)) {
            $transport->setMetricsSessionRegistry($metricsSessionRegistry);
        }

        $messageProcessor = new RedisMessageProcessor($this->envDataProvider, $requestMetadata);

        $logger = new ElkLogger($log, $transport, $messageProcessor);
        if (!is_null($metricsSessionRegistry)) {
            $logger->setMetricsSessionRegistry($metricsSessionRegistry);
        }

        return $logger;
    }


    public function getNativeErrorLogger($log, ?RequestMetadata $requestMetadata = null)
    {
        $transport = new ErrorLogTransport();
        $messageProcessor = new ErrorLogMessageProcessor($this->envDataProvider, $requestMetadata);
        return new ElkLogger($log, $transport, $messageProcessor);
    }
}
