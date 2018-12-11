<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\Redis;

use Psr\Log\LogLevel;
use TutuRu\LoggerElk\ElkLoggerFactory;
use TutuRu\RequestMetadata\RequestMetadata;

class RedisLoggerTest extends RedisBaseTest
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


    public function testLog()
    {
        $requestMetadata = new RequestMetadata();
        $requestMetadata->init();

        $factory = new ElkLoggerFactory();
        $logger = $factory->getRedisLogger('test', $this->config, $this->connectionManager, $requestMetadata);

        $logger->log(LogLevel::WARNING, 'test', ['code' => 'bc']);
        $this->assertEquals(1, $this->getRedisLogList('local')->getLength());
        $message = $this->getRedisLogList('local')->pop();
        $data = json_decode($message, true);

        $this->assertEquals(LogLevel::WARNING, $data['severity']);
        $this->assertEquals('test', $data['log']);
        $this->assertEquals('bc', $data['code']);
        $this->assertEquals(['code' => 'bc'], $data['context']);
        $this->assertEquals(
            [RequestMetadata::ATTR_REQUEST_ID => $requestMetadata->get(RequestMetadata::ATTR_REQUEST_ID)],
            $data['metadata']
        );
    }
}
