<?php

namespace ModSecurity\Model;

class Action
{
    public string $name;
    public ?string $param = null;

    public function __construct(string $name, ?string $param = null)
    {
        $this->name = $name;
        $this->param = $param;
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'param' => $this->param];
    }
}
