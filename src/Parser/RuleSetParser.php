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

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue; // Skip comments and empty lines
            }

            if (substr($line, -1) === '\\') {
                $buffer .= rtrim(substr($line, 0, -1)) . ' ';
                continue;
            } else {
                $buffer .= $line;
                // Full logical rule assembled
            }

            if (stripos($buffer, 'SecRule') === 0) {
                $rule = $this->ruleParser->parse($buffer);

                if ($this->isChainRule($rule)) {
                    if ($parentRule === null) {
                        $parentRule = $rule;
                    } else {
                        $parentRule->addChainedRule($rule);
                    }
                } else {
                    if ($parentRule !== null) {
                        $parentRule->addChainedRule($rule);
                        $rules[] = $parentRule;
                        $parentRule = null;
                    } else {
                        $rules[] = $rule;
                    }
                }
            }

            $buffer = ''; // reset buffer after processing
        }

        if ($parentRule !== null) {
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
