<?php
declare(strict_types=1);

namespace TutuRu\LoggerElk;

class HostnameBasedEnvDataProvider implements EnvDataProviderInterface
{
    /** @var string */
    private $hostname;

    /** @var string */
    private $id;


    public function __construct(string $hostname)
    {
        $this->hostname = $hostname;
    }


    public function getHostname(): string
    {
        return $this->hostname;
    }


    public function getHash(): string
    {
        if (is_null($this->id)) {
            $this->generateHash();
        }
        return $this->id;
    }


    public function generateHash(): void
    {
        $serverId = substr(current(explode('.', $this->getHostname())), 0, 3);
        $serverId = str_pad($serverId, 3, '0', STR_PAD_LEFT);
        $objectId = substr(md5(uniqid()), 1, 3);
        $this->id = $serverId . $objectId;
    }
}
