<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\ErrorLog;

use TutuRu\LoggerElk\EnvDataProviderInterface;
use TutuRu\LoggerElk\MessageProcessorInterface;
use TutuRu\RequestMetadata\RequestMetadata;

class ErrorLogMessageProcessor implements MessageProcessorInterface
{
    private const EMPTY_VALUE = '_';

    /** @var RequestMetadata */
    private $requestMetadata;

    /** @var EnvDataProviderInterface */
    private $envDataProvider;


    public function __construct(EnvDataProviderInterface $envDataProvider, ?RequestMetadata $requestMetadata = null)
    {
        $this->envDataProvider = $envDataProvider;
        $this->requestMetadata = $requestMetadata;
    }


    public function processMessage($log, $level, $message, array $context = []): ?string
    {
        $data = [
            'PID'       => getmypid(),
            'HASH'      => $this->getHash(),
            'SERVICE'   => (string)$log ?: self::EMPTY_VALUE,
            'RequestID' => $this->getRequestId()
        ];
        $prefix = implode(
            " ",
            array_map(
                function ($key, $value) {
                    return "$key: $value";
                },
                array_keys($data),
                $data
            )
        );

        return sprintf(
            "[%s] %s",
            $prefix,
            is_scalar($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE)
        );
    }


    private function getHash(): string
    {
        try {
            return $this->envDataProvider->getHash();
        } catch (\Throwable $e) {
            return self::EMPTY_VALUE;
        }
    }


    private function getRequestId(): string
    {
        try {
            if (!is_null($this->requestMetadata)) {
                return (string)$this->requestMetadata->get(RequestMetadata::ATTR_REQUEST_ID) ?: self::EMPTY_VALUE;
            } else {
                return self::EMPTY_VALUE;
            }
        } catch (\Throwable $e) {
            return self::EMPTY_VALUE;
        }
    }
}
