<?php

namespace ModSecurity\Parser;

class Tokenizer
{
    private string $input;
    private int $position = 0;
    private int $length;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = strlen($input);
    }

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
                $token = $this->readQuotedString();
                if (is_array($token)) {
                    $tokens = array_merge($tokens, $token);
                } else {
                    $tokens[] = $token;
                }
            } elseif ($char === '@') {
                $tokens[] = $this->readOperator();
            } else {
                $tokens[] = $this->readWord();
            }
        }

        return $tokens;
    }

    private function isEOF(): bool
    {
        return $this->position >= $this->length;
    }

    private function peek(): string
    {
        return $this->input[$this->position];
    }

    private function advance(int $steps = 1): void
    {
        $this->position += $steps;
    }

    private function skipWhitespace(): void
    {
        while (!$this->isEOF() && ctype_space($this->peek())) {
            $this->advance();
        }
    }

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
