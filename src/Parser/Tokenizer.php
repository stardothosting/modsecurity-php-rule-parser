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
                $tokens[] = $this->readQuotedString();
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

    private function readQuotedString(): Token
    {
        $this->advance();
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

        return new Token('QUOTED_STRING', $value);
    }

    private function readOperator(): Token
    {
        $this->advance();
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
