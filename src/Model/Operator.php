<?php

namespace Stardothosting\ModSecurity\Model;

/**
 * Represents a ModSecurity rule operator (e.g., @rx, @streq).
 */
class Operator
{
    /**
     * @var string Operator type (e.g., @rx, @streq)
     */
    public string $type;

    /**
     * @var string Operator value (e.g., "admin")
     */
    public string $value;

    /**
     * Operator constructor.
     *
     * @param string $type
     * @param string $value
     */
    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Convert the operator to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return ['type' => $this->type, 'value' => $this->value];
    }
}
