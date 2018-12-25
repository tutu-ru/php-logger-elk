<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk\ErrorLog;

use TutuRu\LoggerElk\TransportInterface;

class ErrorLogTransport implements TransportInterface
{
    public function getName(): string
    {
        return 'error_log';
    }


    public function send(string $message): void
    {
        $message = str_replace("\n", "   _/|\_   ", $message);
        error_log("$message\n", 3, 'php://stderr');
    }
}
