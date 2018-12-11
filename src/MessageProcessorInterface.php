<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

interface MessageProcessorInterface
{
    public function processMessage($log, $level, $message, array $context = []): ?string;
}
