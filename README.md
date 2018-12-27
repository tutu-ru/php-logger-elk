# Библиотека LoggerElk

Реализация PSR-3

## error_log

```php
use TutuRu\LoggerElk\ElkLoggerFactory;

$loggerFactory = new ElkLoggerFactory();
$logger = $loggerFactory->getNativeErrorLogger($logName);
```

## Пуш логов в Redis

```php
use TutuRu\LoggerElk\ElkLoggerFactory;

$loggerFactory = new ElkLoggerFactory();
$logger = $loggerFactory->getRedisLogger(
    $logName,
    $configContainer,
    $redisConnectionManager,
    $requestMetadataOrNull,
    $StatsdExporterClientOrNull
);
```

## Тестирование

Для полного прогона тестов необходим запущенный сервер redis.
Тесты по умолчанию подключаются к серверу по адресу `localhost:6380`.

Запустить можно, например, при помощи docker:
```bash
docker run -d --name test-elk-redis -p 6380:6379 redis
```
