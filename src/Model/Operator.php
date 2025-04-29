<?php

namespace ModSecurity\Model;

class Operator
{
    public string $type;
    public string $value;

    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return ['type' => $this->type, 'value' => $this->value];
    }
}
