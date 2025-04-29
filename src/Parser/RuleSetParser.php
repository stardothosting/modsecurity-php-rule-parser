<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Model\Rule;

/**
 * Parses a set of ModSecurity rules (possibly multiline, chained, or with comments).
 */
class RuleSetParser
{
    /**
     * @var RuleParser
     */
    private RuleParser $ruleParser;

    /**
     * RuleSetParser constructor.
     */
    public function __construct()
    {
        $this->ruleParser = new RuleParser();
    }

    /**
     * Parse a string containing one or more ModSecurity rules.
     *
     * @param string $rawContent The raw rules content (file or string)
     * @return Rule[] Array of parsed Rule objects
     */
    public function parseRules(string $rawContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawContent);
        $rules = [];

        $buffer = '';
        $parentRule = null;
        $expectingChain = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Handle line continuations (ending with backslash)
            if (substr($line, -1) === '\\') {
                $buffer .= rtrim(substr($line, 0, -1)) . ' ';
                continue;
            } else {
                $buffer .= $line;
            }

            // Split buffer into multiple SecRules if present
            $splitRules = $this->splitSecRules($buffer);

            foreach ($splitRules as $ruleString) {
                if (empty(trim($ruleString))) {
                    continue;
                }

                try {
                    $rule = $this->ruleParser->parse('SecRule ' . trim($ruleString));
                } catch (\Exception $e) {
                    // Skip invalid rules
                    $buffer = '';
                    continue;
                }

                // Handle chained rules
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

        // Add any remaining parent rule
        if ($parentRule) {
            $rules[] = $parentRule;
        }

        return $rules;
    }

    /**
     * Determine if a rule is a chain rule (contains the "chain" action).
     *
     * @param Rule $rule
     * @return bool
     */
    private function isChainRule(Rule $rule): bool
    {
        foreach ($rule->actions as $action) {
            if (strtolower($action->name) === 'chain') {
                return true;
            }
        }
        return false;
    }

    /**
     * Split a string into individual SecRule statements.
     *
     * @param string $input
     * @return array
     */
    private function splitSecRules(string $input): array
    {
        $parts = preg_split('/SecRule\s+/i', $input, -1, PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
}
