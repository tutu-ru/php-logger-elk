<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\ErrorLog;

use Psr\Log\LogLevel;
use TutuRu\LoggerElk\ErrorLog\ErrorLogMessageProcessor;
use TutuRu\LoggerElk\HostnameBasedEnvDataProvider;
use TutuRu\RequestMetadata\RequestMetadata;
use TutuRu\Tests\LoggerElk\BaseTest;
use TutuRu\Tests\LoggerElk\Objects\NotStringable;
use TutuRu\Tests\LoggerElk\Objects\Stringable;

class ErrorMessageProcessorTest extends BaseTest
{
    /**
     * @dataProvider messageProcessingDataProvider
     *
     * @param $data
     * @param $expected
     */
    public function testMessageProcessing($data, $expected)
    {
        $messageProcessor = new ErrorLogMessageProcessor(new HostnameBasedEnvDataProvider('localhost'));
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
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => 'some message',
                ]
            ],
            [
                'data'     => [
                    'log'     => 'test',
                    'level'   => 'fire in the hole',
                    'message' => 'last message',
                    'context' => ['debug' => 1, 'code' => 'bc'],
                ],
                'expected' => [
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => 'last message',
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
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => '',
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
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => '{}',
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
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => '{}',
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
                    'pid'        => getmypid(),
                    'log'        => 'test',
                    'request_id' => '_',
                    'message'    => '{"person":{}}',
                ]
            ]
        ];
    }


    public function testMessageProcessingWithRequestMetadata()
    {
        $requestMetadata = new RequestMetadata();
        $requestMetadata->init();

        $messageProcessor = new ErrorLogMessageProcessor(
            new HostnameBasedEnvDataProvider('localhost'),
            $requestMetadata
        );
        $result = $messageProcessor->processMessage('test', 'info', 'msg', []);

        $expected = [
            'pid'        => getmypid(),
            'log'        => 'test',
            'request_id' => $requestMetadata->get(RequestMetadata::ATTR_REQUEST_ID),
            'message'    => 'msg',
        ];
        $this->checkLogRecord($result, $expected);
    }


    private function checkLogRecord($result, $expected)
    {
        if (preg_match('/^\[PID: (\d+) HASH: (\w+) SERVICE: (\w+) RequestID: ([^\]]+)\] (.*)$/', $result, $m)) {
            unset($m[0]); // remove full string
            unset($m[2]); // remove hash
            $this->assertEquals(array_values($m), array_values($expected));
        } else {
            self::fail("result message is invalid: {$result}");
        }
    }
}
