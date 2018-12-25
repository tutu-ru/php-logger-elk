<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\Redis;

use TutuRu\Config\ConfigContainer;
use TutuRu\LoggerElk\Redis\RedisTransport;
use TutuRu\LoggerElk\Redis\RedisTransportConfig;
use TutuRu\Redis\ConnectionManager;
use TutuRu\Redis\RedisList;
use TutuRu\Tests\Config\JsonConfig\JsonConfigFactory;
use TutuRu\Tests\LoggerElk\BaseTest;

abstract class RedisBaseTest extends BaseTest
{
    /** @var ConfigContainer */
    protected $config;

    /** @var ConnectionManager */
    protected $connectionManager;


    public function setUp()
    {
        parent::setUp();
        $this->config = JsonConfigFactory::createConfig(__DIR__ . '/configs/app.json', __DIR__ . '/configs/env.json');
        $this->connectionManager = new ConnectionManager($this->config);
    }


    protected function getRedisTransport(): RedisTransport
    {
        $config = new RedisTransportConfig($this->config);
        $list = $this->connectionManager->createHaPushListGroup($config->getListName(), $config->getConnectionNames());
        return new RedisTransport($list);
    }


    protected function getRedisLogList(string $name): RedisList
    {
        return $this->connectionManager->getConnection($name)->getList('logs-list');
    }


    protected function cleanUp()
    {
        try {
            $this->getRedisLogList('local')->del();
        } catch (\Throwable $e) {
        }
    }
}
