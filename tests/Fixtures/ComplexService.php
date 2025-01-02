<?php

namespace Ananke\Tests\Fixtures;

class ComplexService
{
    private string $id;
    private array $data;

    public function __construct()
    {
        $this->id = uniqid('complex_', true);
        $this->data = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
