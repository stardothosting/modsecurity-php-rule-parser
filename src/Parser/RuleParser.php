<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Model\Rule;
use Stardothosting\ModSecurity\Model\Variable;
use Stardothosting\ModSecurity\Model\Operator;
use Stardothosting\ModSecurity\Model\Action;

class RuleParser
{
    public function parse(string $rawRule): Rule
    {
        $tokenizer = new Tokenizer($rawRule);
        $tokens = $tokenizer->tokenize();

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

        $opArg = $opValToken ? $opValToken->value : '';
        $operator = new Operator($opType, $opArg);

        $actions = [];
        while ($tokenIndex < count($tokens)) {
            $token = $tokens[$tokenIndex++];
            if ($token->type !== 'QUOTED_STRING') continue;

            foreach (explode(',', $token->value) as $a) {
                $a = trim($a);
                if (!$a) continue;

                if (strpos($a, ':') !== false) {
                    [$name, $param] = explode(':', $a, 2);
                    $actions[] = new Action(trim($name), trim($param));
                } else {
                    $actions[] = new Action(trim($a), null);
                }
            }
        }

        return new Rule($variables, $operator, $actions);
    }
}
