<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Model\Rule;

class RuleSetParser
{
    private RuleParser $ruleParser;

    public function __construct()
    {
        $this->ruleParser = new RuleParser();
    }

    public function parseRules(string $rawContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawContent);
        $rules = [];
        $buffer = '';
        $parentRule = null;
        $expectingChain = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                if ($expectingChain && $buffer !== '') {
                    $buffer .= ' ';
                }
                continue;
            }

            if (substr($line, -1) === '\\') {
                $buffer .= rtrim(substr($line, 0, -1)) . ' ';
                continue;
            }

            $buffer .= $line;

            $splitRules = preg_split('/(?=SecRule\s+)/i', $buffer, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($splitRules as $ruleString) {
                try {
                    $rule = $this->ruleParser->parse(trim($ruleString));
                } catch (\Throwable $e) {
                    $buffer = '';
                    continue;
                }

                if ($expectingChain && $parentRule) {
                    $parentRule->addChainedRule($rule);
                    $expectingChain = $this->isChainRule($rule);
                } elseif ($this->isChainRule($rule)) {
                    $parentRule = $rule;
                    $expectingChain = true;
                } else {
                    if ($parentRule) {
                        $rules[] = $parentRule;
                        $parentRule = null;
                    }
                    $rules[] = $rule;
                    $expectingChain = false;
                }
            }

            $buffer = '';
        }

        if ($parentRule) {
            $rules[] = $parentRule;
        }

        return $rules;
    }

    private function isChainRule(Rule $rule): bool
    {
        foreach ($rule->actions as $action) {
            if (strtolower($action->name) === 'chain') {
                return true;
            }
        }
        return false;
    }
}
