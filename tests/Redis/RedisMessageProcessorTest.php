<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\Redis;

use Psr\Log\LogLevel;
use TutuRu\LoggerElk\HostnameBasedEnvDataProvider;
use TutuRu\LoggerElk\Redis\RedisMessageProcessor;
use TutuRu\RequestMetadata\RequestMetadata;
use TutuRu\Tests\LoggerElk\Objects\NotStringable;
use TutuRu\Tests\LoggerElk\Objects\Stringable;

class RedisMessageProcessorTest extends RedisBaseTest
{
    /**
     * @dataProvider messageProcessingDataProvider
     *
     * @param $data
     * @param $expected
     */
    public function testMessageProcessing($data, $expected)
    {
        $messageProcessor = new RedisMessageProcessor(new HostnameBasedEnvDataProvider('localhost'));
        $result = $messageProcessor->processMessage($data['log'], $data['level'], $data['message'], $data['context']);
        $this->checkLogRecord($result, $expected);
    }


    public function messageProcessingDataProvider()
    {
        return [
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => LogLevel::WARNING,
                    'message' => 'some message',
                    'context' => [],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => LogLevel::WARNING,
                    'code'     => '',
                    'message'  => 'some message',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => 'fire in the hole',
                    'message' => 'last message',
                    'context' => ['debug' => 1, '__log_code__' => 'bc'],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => 'fire in the hole',
                    'code'     => 'bc',
                    'message'  => 'last message',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                    'context'  => ['debug' => 1]
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => LogLevel::WARNING,
                    'message' => [0 => INF, 'some_key' => NAN],
                    'context' => [],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => LogLevel::WARNING,
                    'code'     => '',
                    'message'  => '{"0":null,"some_key":null}',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => LogLevel::WARNING,
                    'message' => new Stringable('test', 2018),
                    'context' => [],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => LogLevel::WARNING,
                    'code'     => '',
                    'message'  => Stringable::class . ': test:2018',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => LogLevel::WARNING,
                    'message' => new NotStringable('test', 2018),
                    'context' => [],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => LogLevel::WARNING,
                    'code'     => '',
                    'message'  => 'Cannot log ' . NotStringable::class . ' object',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => LogLevel::WARNING,
                    'message' => ['person' => new NotStringable('test', 2018)],
                    'context' => [],
                ],
                'expected' => [
                    'log'      => 'test',
                    'severity' => LogLevel::WARNING,
                    'code'     => '',
                    'message'  => '{"person":{}}',
                    'host'     => 'localhost',
                    'pid'      => getmypid(),
                ]
            ]
        ];
    }


    public function testMessageProcessingWithRequestMetadata()
    {
        $requestMetadata = new RequestMetadata();
        $requestMetadata->init();

        $messageProcessor = new RedisMessageProcessor(new HostnameBasedEnvDataProvider('localhost'), $requestMetadata);
        $result = $messageProcessor->processMessage('test', 'info', 'msg', []);

        $expected = [
            'log'      => 'test',
            'severity' => 'info',
            'code'     => '',
            'message'  => 'msg',
            'host'     => 'localhost',
            'pid'      => getmypid(),
            'metadata' => [RequestMetadata::ATTR_REQUEST_ID => $requestMetadata->get(RequestMetadata::ATTR_REQUEST_ID)]
        ];
        $this->checkLogRecord($result, $expected);
    }


    private function checkLogRecord($result, $expected)
    {
        $decodedResult = json_decode($result, true);

        $this->assertArrayHasKey('@timestamp', $decodedResult);
        $this->assertArrayHasKey('hash', $decodedResult);
        unset($decodedResult['@timestamp'], $decodedResult['hash']);

        $this->assertEquals($expected, $decodedResult);
    }
}
