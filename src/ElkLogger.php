<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use Psr\Log\AbstractLogger;

class ElkLogger extends AbstractLogger
{
    private $log;

    private $messageProcessor;

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
        try {
            if (!is_null($this->messageProcessor)) {
                $message = $this->messageProcessor->processMessage($this->log, $level, $message, $context);
            } else {
                $message = (string)$message;
            }

            if (!is_null($message)) {
                $this->transport->send($message);
            }
        } catch (\Throwable $e) {
            // TODO: failover
        }
    }
}
