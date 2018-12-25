<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

interface TransportInterface
{
    public function getName(): string;

    /**
     * @param string $message
     *
     * @throws TransportNotAvailableExceptionInterface
     * @return void
     */
    public function send(string $message): void;
}
