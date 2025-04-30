<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Model\Rule;
use Stardothosting\ModSecurity\Model\Variable;
use Stardothosting\ModSecurity\Model\Operator;
use Stardothosting\ModSecurity\Model\Action;

class RuleParser
{
    private Tokenizer $tokenizer;

    public function parse(string $rawRule): Rule
    {
        $this->tokenizer = new Tokenizer($rawRule);
        $tokens = $this->tokenizer->tokenize();

        $tokenIndex = 0;

        if (!isset($tokens[$tokenIndex]) || $tokens[$tokenIndex++]->value !== 'SecRule') {
            throw new \Exception("Expected SecRule");
        }

        $varToken = $tokens[$tokenIndex++] ?? null;
        $opToken = $tokens[$tokenIndex++] ?? null;
        $opValToken = $tokens[$tokenIndex++] ?? null;

        if (!$varToken || !$opToken) {
            throw new \Exception("Failed to parse rule: $rawRule");
        }

        $variables = [];
        foreach (explode('|', $varToken->value) as $v) {
            [$name, $key] = array_pad(explode(':', $v, 2), 2, null);
            $variables[] = new Variable(trim($name), $key ? trim($key) : null);
        }

        $opType = $opToken->value;
        if (!str_starts_with($opType, '@')) {
            $opType = '@' . $opType;
        }

        $operator = null;
        $actions = [];

        if ($opValToken) {
            $opArg = $opValToken->value;
            // If the operator argument string starts with known action, treat as action string
            if (preg_match('/^\s*(id|phase|deny|allow|pass|msg|log|status|tag|t|ctl|setvar|ver)/i', $opArg)) {
                $actions = $this->parseActionsFromString($opArg);
            } else {
                $operator = new Operator($opType, $opArg);
            }
        }

        // Continue parsing any additional quoted action strings
        while ($tokenIndex < count($tokens)) {
            $token = $tokens[$tokenIndex++];
            if ($token->type !== 'QUOTED_STRING') continue;

            $actions = array_merge($actions, $this->parseActionsFromString($token->value));
        }

        return new Rule($variables, $operator, $actions);
    }

    private function parseActionsFromString(string $raw): array
    {
        $actions = [];
        foreach (explode(',', $raw) as $a) {
            $a = trim($a);
            if (!$a) continue;

            if (strpos($a, ':') !== false) {
                [$name, $param] = explode(':', $a, 2);
                $actions[] = new Action(trim($name), trim($param));
            } else {
                $actions[] = new Action(trim($a), null);
            }
        }
        return $actions;
    }
}
