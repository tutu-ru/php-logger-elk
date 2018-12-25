<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use Psr\Log\AbstractLogger;
use TutuRu\Metrics\MetricAwareInterface;
use TutuRu\Metrics\MetricAwareTrait;

class ElkLogger extends AbstractLogger implements MetricAwareInterface
{
    use MetricAwareTrait;

    /** @var string */
    private $log;

    /** @var MessageProcessorInterface */
    private $messageProcessor;

    /** @var TransportInterface */
    private $transport;

    private $defaultMetricTags = [];


    public function __construct(
        string $log,
        TransportInterface $transport,
        ?MessageProcessorInterface $messageProcessor = null
    ) {
        $this->log = $log;
        $this->transport = $transport;
        $this->messageProcessor = $messageProcessor;
        $this->defaultMetricTags = [
            'log'       => $this->log,
            'transport' => $this->transport->getName(),
        ];
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
            $this->sendMetricsAboutError('not_available');
        } catch (\Exception $e) {
            $this->sendMetricsAboutError('save_exception');
        }
    }


    private function sendMetricsAboutError(string $error)
    {
        if (!is_null($this->statsdExporterClient)) {
            $this->statsdExporterClient->increment(
                'log_elk_error',
                array_merge($this->defaultMetricTags, ['error' => $error])
            );
        }
    }
}
