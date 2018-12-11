<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

interface EnvDataProviderInterface
{
    public function getHostname(): string;

    public function getHash(): string;

    public function generateHash(): void;
}
