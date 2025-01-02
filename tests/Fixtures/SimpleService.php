<?php

namespace Ananke\Tests\Fixtures;

class SimpleService
{
    private string $id;

    public function __construct()
    {
        $this->id = uniqid('simple_', true);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
