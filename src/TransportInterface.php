<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

interface TransportInterface
{
    /**
     * @param string $message
     *
     * @throws TransportNotAvailableExceptionInterface
     * @return void
     */
    public function send(string $message): void;
}
