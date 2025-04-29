<?php

namespace ModSecurity\Parser;

use ModSecurity\Model\Rule;

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
                continue; // skip comments
            }

            if (substr($line, -1) === '\\') {
                $buffer .= rtrim(substr($line, 0, -1)) . ' ';
                continue;
            } else {
                $buffer .= $line;
            }

            // Now, split buffered block into multiple SecRules if necessary
            $splitRules = $this->splitSecRules($buffer);

            foreach ($splitRules as $ruleString) {
                if (empty(trim($ruleString))) {
                    continue;
                }

                try {
                    $rule = $this->ruleParser->parse('SecRule ' . trim($ruleString));
                } catch (\Exception $e) {
                    $buffer = '';
                    continue;
                }

                if ($expectingChain) {
                    if ($parentRule) {
                        $parentRule->addChainedRule($rule);
                    }
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

    private function splitSecRules(string $input): array
    {
        $parts = preg_split('/SecRule\s+/i', $input, -1, PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
}
