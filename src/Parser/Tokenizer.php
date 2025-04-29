<?php

namespace Stardothosting\ModSecurity\Parser;

/**
 * Tokenizes a ModSecurity rule string into tokens for parsing.
 */
class Tokenizer
{
    /**
     * @var string The input string to tokenize
     */
    private string $input;

    /**
     * @var int Current position in the input string
     */
    private int $position = 0;

    /**
     * @var int Length of the input string
     */
    private int $length;

    /**
     * Tokenizer constructor.
     *
     * @param string $input The rule string to tokenize
     */
    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = strlen($input);
    }

    /**
     * Tokenize the input string into an array of Token objects.
     *
     * @return Token[]
     */
    public function tokenize(): array
    {
        $tokens = [];

        while (!$this->isEOF()) {
            $this->skipWhitespace();

            if ($this->isEOF()) {
                break;
            }

            $char = $this->peek();

            if ($char === '"') {
                // Handle quoted strings (actions, operator values, etc.)
                $token = $this->readQuotedString();
                if (is_array($token)) {
                    $tokens = array_merge($tokens, $token);
                } else {
                    $tokens[] = $token;
                }
            } elseif ($char === '@') {
                // Handle operator tokens
                $tokens[] = $this->readOperator();
            } else {
                // Handle words (SecRule, variable names, etc.)
                $tokens[] = $this->readWord();
            }
        }

        return $tokens;
    }

    /**
     * Check if end of input is reached.
     *
     * @return bool
     */
    private function isEOF(): bool
    {
        return $this->position >= $this->length;
    }

    /**
     * Peek at the current character.
     *
     * @return string
     */
    private function peek(): string
    {
        return $this->input[$this->position];
    }

    /**
     * Advance the current position.
     *
     * @param int $steps
     */
    private function advance(int $steps = 1): void
    {
        $this->position += $steps;
    }

    /**
     * Skip whitespace characters.
     */
    private function skipWhitespace(): void
    {
        while (!$this->isEOF() && ctype_space($this->peek())) {
            $this->advance();
        }
    }

    /**
     * Read a quoted string token (handles escaped quotes and special operator cases).
     *
     * @return Token|Token[]
     */
    private function readQuotedString()
    {
        $this->advance(); // Skip opening quote
        $value = '';
        $escaped = false;

        while (!$this->isEOF()) {
            $char = $this->peek();
            $this->advance();

            if ($escaped) {
                $value .= $char;
                $escaped = false;
            } elseif ($char === '\\') {
                $escaped = true;
            } elseif ($char === '"') {
                break;
            } else {
                $value .= $char;
            }
        }

        // Handle quoted operator-only value (e.g., "@detectXSS")
        if (preg_match('/^(!?@)(\w+)$/', $value, $matches)) {
            $operator = ($matches[1] === '!@') ? '!' . $matches[2] : $matches[2];
            return new Token('OPERATOR', $operator);
        }

        // Handle @operator + space + value (e.g., '@rx foo')
        if (preg_match('/^(!?@)(\w+)\s+(.+)$/', $value, $matches)) {
            $operator = ($matches[1] === '!@') ? '!' . $matches[2] : $matches[2];
            return [
                new Token('OPERATOR', $operator),
                new Token('QUOTED_STRING', $matches[3])
            ];
        }

        return new Token('QUOTED_STRING', $value);
    }

    /**
     * Read an operator token (starts with @).
     *
     * @return Token
     */
    private function readOperator(): Token
    {
        $this->advance(); // Skip @
        $value = '';

        while (!$this->isEOF() && !ctype_space($this->peek())) {
            $value .= $this->peek();
            $this->advance();
        }

        return new Token('OPERATOR', $value);
    }

    /**
     * Read a word token (until whitespace).
     *
     * @return Token
     */
    private function readWord(): Token
    {
        $value = '';

        while (!$this->isEOF() && !ctype_space($this->peek())) {
            $value .= $this->peek();
            $this->advance();
        }

        return new Token('WORD', $value);
    }
}
