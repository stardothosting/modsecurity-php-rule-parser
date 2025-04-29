<?php

namespace Stardothosting\ModSecurity\Model;

/**
 * Represents a ModSecurity rule action (e.g., id, phase, deny).
 */
class Action
{
    /**
     * @var string Action name (e.g., id, phase, deny)
     */
    public string $name;

    /**
     * @var string|null Optional parameter for the action (e.g., id:1001)
     */
    public ?string $param = null;

    /**
     * Action constructor.
     *
     * @param string $name
     * @param string|null $param
     */
    public function __construct(string $name, ?string $param = null)
    {
        $this->name = $name;
        $this->param = $param;
    }

    /**
     * Convert the action to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'param' => $this->param];
    }
}
