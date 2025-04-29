<?php

namespace Stardothosting\ModSecurity\Parser;

/**
 * Represents a single token in the parsing process.
 */
class Token
{
    /**
     * @var string The type of token (e.g., WORD, OPERATOR, QUOTED_STRING)
     */
    public string $type;

    /**
     * @var string The value of the token
     */
    public string $value;

    /**
     * Token constructor.
     *
     * @param string $type
     * @param string $value
     */
    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
}
