<?php

namespace Stardothosting\ModSecurity\Model;

/**
 * Represents a ModSecurity rule variable (e.g., ARGS, REQUEST_URI).
 */
class Variable
{
    /**
     * @var string Variable name (e.g., ARGS, REQUEST_URI)
     */
    public string $name;

    /**
     * @var string|null Optional key for the variable (e.g., ARGS:username)
     */
    public ?string $key = null;

    /**
     * Variable constructor.
     *
     * @param string $name
     * @param string|null $key
     */
    public function __construct(string $name, ?string $key = null)
    {
        $this->name = $name;
        $this->key = $key;
    }

    /**
     * Convert the variable to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'key' => $this->key];
    }
}
