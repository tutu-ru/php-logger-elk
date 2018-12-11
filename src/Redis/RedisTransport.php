<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\Redis;

use TutuRu\LoggerElk\TransportInterface;
use TutuRu\Metrics\MetricNameUtils;
use TutuRu\Metrics\MetricsAwareInterface;
use TutuRu\Metrics\MetricsAwareTrait;
use TutuRu\Metrics\SessionNames;
use TutuRu\Redis\ConnectionManager;
use TutuRu\Redis\Exceptions\NoAvailableConnectionsException;
use TutuRu\Redis\HaSingleListPush;

class RedisTransport implements TransportInterface, MetricsAwareInterface
{
    use MetricsAwareTrait;

    private const ERROR_LOG = 'redis_exception';
    private const LIST_NAME = 'logs-list';

    /** @var RedisTransportConfig */
    private $config;

    /** @var ConnectionManager */
    private $connectionManager;

    /** @var HaSingleListPush */
    private $list;

    /** @var bool */
    private $isAvailable = true;


    public function __construct(RedisTransportConfig $config, ConnectionManager $connectionManager)
    {
        $this->config = $config;
        $this->connectionManager = $connectionManager;
    }


    public function send(string $message): void
    {
        if (!$this->checkAvailable()) {
            throw new RedisNotAvailableException("Not available");
        }
        try {
            $this->getList()->push($message);
        } catch (NoAvailableConnectionsException $e) {
            $this->isAvailable = false;
            throw new RedisNotAvailableException($e->getMessage(), $e->getCode(), $e);
        }
    }


    public function exceptionHandler(\Exception $exception)
    {
        $this->sendMetricsAboutException($this->getMetricNameByException($exception));
    }


    private function getList(): HaSingleListPush
    {
        if (empty($this->list)) {
            $this->list = $this->connectionManager->createHASingleListPush(
                self::LIST_NAME,
                $this->config->getConnectionNames()
            );
            $this->list->setRetryTimeout($this->config->getRetryTimeoutForRedisInSec());
            $this->list->setStatsdPrefix('logstash');
            $this->list->setExceptionHandler([$this, 'exceptionHandler']);
        }
        return $this->list;
    }


    private function sendMetricsAboutException(string $key)
    {
        if (!is_null($this->metricsSessionRegistry)) {
            $this->metricsSessionRegistry
                ->getRequestedSessionOrNull(SessionNames::NAME_GARBAGE)
                ->increment(
                    RedisTransportConfig::METRIC_PREFIX . 'transport.redis.exceptions.' .
                    MetricNameUtils::prepareMetricName($key)
                );
        }
    }


    private function getMetricNameByException(\Exception $exception): string
    {
        $shortClassName = substr(strrchr(get_class($exception), "\\"), 1);
        return strtolower(preg_replace('#([a-z])([A-Z])#', "$1_$2", $shortClassName));
    }


    private function checkAvailable(): bool
    {
        if (!$this->isAvailable && $this->getList()->isAvailable()) {
            $this->isAvailable = true;
        }
        return $this->isAvailable;
    }
}
