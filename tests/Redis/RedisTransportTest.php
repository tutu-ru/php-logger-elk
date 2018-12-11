<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\Redis;

use TutuRu\LoggerElk\TransportNotAvailableExceptionInterface;

class RedisTransportTest extends RedisBaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanUp();
    }


    public function tearDown()
    {
        $this->cleanUp();
        parent::tearDown();
    }


    public function testSend()
    {
        $transport = $this->getRedisTransport();
        for ($i = 0; $i < 10; $i++) {
            $transport->send('test ' . $i);
        }
        $this->assertEquals(10, $this->getRedisLogList('local')->getLength());
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals('test ' . $i, $this->getRedisLogList('local')->pop());
        }
    }


    public function testNotAvailableServer()
    {
        $this->config->setApplicationValue('logstash.redis.connections', ['name' => ['not_available']]);

        $this->expectException(TransportNotAvailableExceptionInterface::class);
        $this->expectExceptionMessage("No available connections for Redis");
        $this->getRedisTransport()->send("test");
    }


    public function testRetryNotAvailableServer()
    {
        $this->config->setApplicationValue('logstash.redis.connections', ['name' => ['not_available']]);

        $transport = $this->getRedisTransport();
        try {
            $transport->send("test");
        } catch (TransportNotAvailableExceptionInterface $e) {
        }

        $this->expectException(TransportNotAvailableExceptionInterface::class);
        $this->expectExceptionMessage("Not available");
        $transport->send("test");
    }
}
