<?php

namespace App\Models;

class Person
{
    private string $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Name cannot be empty.');
        }

        $this->name = $name;
    }

    public function getRole(): string
    {
        return 'Person';
    }
}
