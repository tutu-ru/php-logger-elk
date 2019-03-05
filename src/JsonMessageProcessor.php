<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

use TutuRu\RequestMetadata\RequestMetadata;

class JsonMessageProcessor implements MessageProcessorInterface
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:s.uP';
    public const BC_CODE_FIELD = '__log_code__';
    private const CUT_SUFFIX = '...[cutted]...';

    /** @var EnvDataProviderInterface */
    private $endDataProvider;

    /** @var ?RequestMetadata */
    private $requestMetadata;

    /** @var ?int */
    private $maxBytes;


    public function __construct(
        EnvDataProviderInterface $envDataProvider,
        ?RequestMetadata $requestMetadata = null,
        ?int $maxBytes = null
    ) {
        $this->endDataProvider = $envDataProvider;
        $this->requestMetadata = $requestMetadata;
        $this->maxBytes = $maxBytes;
    }


    public function processMessage($log, $level, $message, array $context = []): ?string
    {
        $data = [
            '@timestamp' => (new \DateTime())->format(self::DATE_FORMAT),
            'log'        => $log,
            'severity'   => (string)$level,
            'code'       => (string)($context[self::BC_CODE_FIELD] ?? ''), //bc
            'hash'       => $this->endDataProvider->getHash(),
            'host'       => $this->endDataProvider->getHostname(),
            'pid'        => (string)getmypid(),
            'message'    => $this->prepareMessage($message),
        ];
        unset($context[self::BC_CODE_FIELD]);

        if (!is_null($this->requestMetadata)) {
            if ($metadata = $this->requestMetadata->getPlainAttributes()) {
                $data['metadata'] = $metadata;
            }
        }

        if (!empty($context)) {
            $data['context'] = [];
            foreach ($context as $key => $value) {
                $data['context'][(string)$key] = is_scalar($value) ? (string)$value : $this->toJson($value);
            }
        }

        $result = $this->toJson($data);

        // не пытаемся вычислять что-то заранее, так как выход за пределы лимита редкое событие
        // поэтому эффективней делать проверку после кодирования и урезать на точно известную величину сообщение
        // с последующим повторным кодированием
        if (!is_null($this->maxBytes) && ($len = strlen($result)) >= $this->maxBytes) {
            $overhead = $len - $this->maxBytes;
            $data['message'] = substr($data['message'], 0, -($overhead + strlen(self::CUT_SUFFIX))) . self::CUT_SUFFIX;
            $result = $this->toJson($data);
        }

        return $result;
    }


    private function toJson($data, bool $allowFixNanInf = true): ?string
    {
        $result = json_encode($data, JSON_UNESCAPED_UNICODE);
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            if ($allowFixNanInf && $error == JSON_ERROR_INF_OR_NAN) {
                return $this->toJson(unserialize(str_replace(['d:NAN;', 'd:INF;'], 'N;', serialize($data))), false);
            }
            //skip for now, still working on decision
            return null;
        } else {
            return $result;
        }
    }


    private function prepareMessage($message): ?string
    {
        if (is_scalar($message)) {
            return (string)$message;
        }

        $json = $this->toJson($message);

        // fallback на старый конвертер, если внутри $message сложный объект
        if ('{}' === $json) {
            if (is_object($message)) {
                $json = $this->processObject($message);
            } else {
                $json = var_export($message, true) . ";";
            }
        }

        return $json;
    }


    private function processObject($data)
    {
        $className = get_class($data);
        return method_exists($data, '__toString')
            ? $className . ': ' . strval($data)
            : 'Cannot log ' . $className . ' object';
    }
}
