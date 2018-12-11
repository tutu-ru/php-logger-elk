<?php
declare(strict_types=1);

namespace TutuRu\Tests\LoggerElk\Objects;

class NotStringable
{
    private $name;
    private $age;

    public function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
}
