<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use Psr\Log\AbstractLogger;
use TutuRu\Metrics\MetricNameUtils;
use TutuRu\Metrics\MetricsAwareInterface;
use TutuRu\Metrics\MetricsAwareTrait;
use TutuRu\Metrics\SessionNames;

class ElkLogger extends AbstractLogger implements MetricsAwareInterface
{
    use MetricsAwareTrait;

    public const METRIC_PREFIX = 'low_level.log_elastic.';

    /** @var string */
    private $log;

    /** @var MessageProcessorInterface */
    private $messageProcessor;

    /** @var TransportInterface */
    private $transport;


    public function __construct(
        string $log,
        TransportInterface $transport,
        ?MessageProcessorInterface $messageProcessor = null
    ) {
        $this->log = $log;
        $this->transport = $transport;
        $this->messageProcessor = $messageProcessor;
    }


    public function log($level, $message, array $context = [])
    {
        if (!is_null($this->messageProcessor)) {
            $message = $this->messageProcessor->processMessage($this->log, $level, $message, $context);
        } else {
            $message = (string)$message;
        }

        if (is_null($message)) {
            return;
        }

        try {
            $this->transport->send($message);
        } catch (TransportNotAvailableExceptionInterface $e) {
            $this->sendMetricsAboutError($e->getTransportName() . '_not_available');
        } catch (\Exception $e) {
            $this->sendMetricsAboutError('save_exception');
        }
    }


    private function sendMetricsAboutError(string $name)
    {
        if (!is_null($this->metricsSessionRegistry)) {
            $this->metricsSessionRegistry->getRequestedSessionOrNull(SessionNames::NAME_GARBAGE)
                ->increment(self::METRIC_PREFIX . $name)
                ->increment(self::METRIC_PREFIX . MetricNameUtils::prepareMetricName($this->log) . $name);
        }
    }
}
