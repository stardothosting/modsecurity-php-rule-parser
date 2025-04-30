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

        $varToken = $tokens[$tokenIndex++];
        $operatorToken = $tokens[$tokenIndex++] ?? null;
        $operatorValToken = $tokens[$tokenIndex++] ?? null;

        $variables = [];
        foreach (explode('|', $varToken->value) as $v) {
            [$name, $key] = array_pad(explode(':', $v, 2), 2, null);
            $variables[] = new Variable(trim($name), $key ? trim($key) : null);
        }

        $opType = $operatorToken ? $operatorToken->value : '@rx';
        if (!str_starts_with($opType, '@')) $opType = '@' . $opType;

        $operator = new Operator($opType, $operatorValToken ? $operatorValToken->value : '');

        $actions = [];
        while ($tokenIndex < count($tokens)) {
            $token = $tokens[$tokenIndex++];
            if ($token->type !== 'QUOTED_STRING') continue;

            foreach (explode(',', $token->value) as $a) {
                $a = trim($a);
                if (!$a) continue;
                [$name, $param] = array_pad(explode(':', $a, 2), 2, null);
                $actions[] = new Action(trim($name), $param ? trim($param) : null);
            }
        }

        return new Rule($variables, $operator, $actions);
    }
}
