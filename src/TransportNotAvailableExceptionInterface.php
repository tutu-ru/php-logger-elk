<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

interface TransportNotAvailableExceptionInterface
{
    public function getTransportName(): string;
}
