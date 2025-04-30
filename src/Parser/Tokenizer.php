<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Parser\Token;

class Tokenizer
{
    private string $input;
    private int $length;
    private int $position;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = strlen($input);
        $this->position = 0;
    }

    public function tokenize(): array
    {
        $tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();
            if ($this->position >= $this->length) break;

            $char = $this->input[$this->position];

            if ($char === '"' || $char === "'") {
                $tokens[] = new Token('QUOTED_STRING', $this->readQuotedString($char));
            } elseif (ctype_alpha($char) || $char === '@') {
                $tokens[] = new Token('WORD', $this->readWord());
            } else {
                $this->position++;
            }
        }

        return $tokens;
    }

    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ctype_space($this->input[$this->position])) {
            $this->position++;
        }
    }

    private function readQuotedString(string $quote): string
    {
        $this->position++;  // Skip opening quote
        $value = '';

        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === '\\' && $this->position + 1 < $this->length) {
                $value .= $this->input[$this->position + 1];
                $this->position += 2;
            } elseif ($char === $quote) {
                $this->position++;
                break;
            } else {
                $value .= $char;
                $this->position++;
            }
        }

        return $value;
    }

    private function readWord(): string
    {
        $start = $this->position;
        while ($this->position < $this->length &&
               (ctype_alnum($this->input[$this->position]) || $this->input[$this->position] === ':' || $this->input[$this->position] === '@' || $this->input[$this->position] === '_' || $this->input[$this->position] === '-' || $this->input[$this->position] === '|')) {
            $this->position++;
        }

        return substr($this->input, $start, $this->position - $start);
    }
}
