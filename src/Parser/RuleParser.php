<?php

namespace ModSecurity\Parser;

use ModSecurity\Model\Rule;
use ModSecurity\Model\Variable;
use ModSecurity\Model\Operator;
use ModSecurity\Model\Action;

class RuleParser
{
    public function parse(string $ruleString): Rule
    {
        $tokenizer = new Tokenizer($ruleString);
        $tokens = $tokenizer->tokenize();

        if (empty($tokens)) {
            throw new \Exception("No tokens found in rule: $ruleString");
        }

        // Parse basic rule components
        $tokenIndex = 0;

        // 1. Expect 'SecRule'
        $this->expectWord($tokens[$tokenIndex++], 'SecRule');

        // 2. Variables
        $variableToken = $this->expectType($tokens[$tokenIndex++], 'WORD');
        $variables = $this->parseVariables($variableToken->value);

        // 3. Operator
        $operatorToken = $this->expectType($tokens[$tokenIndex++], 'OPERATOR');
        $operatorValueToken = $this->expectType($tokens[$tokenIndex++], 'QUOTED_STRING');
        $operator = new Operator($operatorToken->value, $operatorValueToken->value);

        // 4. Actions
        $actions = [];
        if (isset($tokens[$tokenIndex])) {
            $actionToken = $this->expectType($tokens[$tokenIndex], 'QUOTED_STRING');
            $actions = $this->parseActions($actionToken->value);
        }

        return new Rule($variables, $operator, $actions);
    }

    private function parseVariables(string $value): array
    {
        $parts = explode('|', $value);
        $variables = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, ':') !== false) {
                [$name, $key] = explode(':', $part, 2);
                $variables[] = new Variable($name, $key);
            } else {
                $variables[] = new Variable($part, null);
            }
        }

        return $variables;
    }

    private function parseActions(string $actionsString): array
    {
        $parts = explode(',', $actionsString);
        $actions = [];

        foreach ($parts as $actionPart) {
            $actionPart = trim($actionPart);
            if (strpos($actionPart, ':') !== false) {
                [$name, $param] = explode(':', $actionPart, 2);
                $actions[] = new Action($name, $param);
            } else {
                $actions[] = new Action($actionPart, null);
            }
        }

        return $actions;
    }

    private function expectWord(Token $token, string $expected): void
    {
        if ($token->type !== 'WORD' || strtolower($token->value) !== strtolower($expected)) {
            throw new \Exception("Expected WORD '$expected', got {$token->type} '{$token->value}'");
        }
    }

    private function expectType(Token $token, string $expectedType): Token
    {
        if ($token->type !== $expectedType) {
            throw new \Exception("Expected $expectedType, got {$token->type} '{$token->value}'");
        }
        return $token;
    }
}
