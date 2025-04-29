<?php

namespace ModSecurity\Model;

class Variable
{
    public string $name;
    public ?string $key = null;

    public function __construct(string $name, ?string $key = null)
    {
        $this->name = $name;
        $this->key = $key;
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'key' => $this->key];
    }
}
