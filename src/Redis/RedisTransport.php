<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\Redis;

use TutuRu\LoggerElk\TransportInterface;
use TutuRu\Redis\Exceptions\NoAvailableConnectionsException;
use TutuRu\Redis\RedisPushListInterface;

class RedisTransport implements TransportInterface
{
    /** @var RedisPushListInterface */
    private $pushList;

    /** @var bool */
    private $isAvailable = true;


    public function __construct(RedisPushListInterface $pushList)
    {
        $this->pushList = $pushList;
    }


    public function getName(): string
    {
        return 'redis';
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


    private function getList(): RedisPushListInterface
    {
        return $this->pushList;
    }


    private function checkAvailable(): bool
    {
        if (!$this->isAvailable && $this->getList()->isAvailable()) {
            $this->isAvailable = true;
        }
        return $this->isAvailable;
    }
}
