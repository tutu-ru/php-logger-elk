<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\Redis;

use TutuRu\LoggerElk\TransportNotAvailableExceptionInterface;

class RedisNotAvailableException extends \Exception implements TransportNotAvailableExceptionInterface
{
}
