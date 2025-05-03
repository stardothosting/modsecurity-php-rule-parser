<?php

namespace ModSecurity\Parser;

use ModSecurity\Model\Rule;
use ModSecurity\Model\Variable;
use ModSecurity\Model\Operator;
use ModSecurity\Model\Action;
use ModSecurity\Model\Chain;

/**
 * Parses a single ModSecurity SecRule string into a Rule object.
 */
class RuleParser
{
    /**
     * @var Tokenizer Tokenizer instance for the current rule
     */
    private Tokenizer $tokenizer;

    /**
     * Parse a raw SecRule string into a Rule object.
     *
     * @param string $rawRule The raw SecRule string (without leading/trailing whitespace)
     * @return Rule
     * @throws \Exception If the rule cannot be parsed
     */
    public function parse(string $rawRule): Rule
    {
        $this->tokenizer = new Tokenizer($rawRule);
        $tokens = $this->tokenizer->tokenize();

        if (count($tokens) < 4) {
            throw new \Exception("Failed to parse rule: $rawRule");
        }

        $tokenIndex = 0;

        // Expect: SecRule
        $secRule = $tokens[$tokenIndex++];
        if ($secRule->value !== 'SecRule') {
            throw new \Exception("Expected SecRule, got {$secRule->value}");
        }

        // Parse variables (can be pipe-separated)
        $variablesToken = $tokens[$tokenIndex++];
        $variables = explode('|', $variablesToken->value);
        $variablesList = [];

        foreach ($variables as $variable) {
            $variablesList[] = new Variable($variable);
        }

        // Parse operator type and value
        $operatorTypeToken = $tokens[$tokenIndex++];
        $operatorValueToken = $tokens[$tokenIndex++];

        $operatorType = $operatorTypeToken->value;
        if (!str_starts_with($operatorType, '@')) {
            $operatorType = '@' . $operatorType;
        }

        $operator = new Operator($operatorType, $operatorValueToken->value);

        // Parse actions (comma-separated, possibly with parameters)
        $actions = [];
        while ($tokenIndex < count($tokens)) {
            $token = $tokens[$tokenIndex++];
            if ($token->type !== 'QUOTED_STRING') {
                continue;
            }

            $actionList = explode(',', $token->value);
            foreach ($actionList as $actionEntry) {
                $actionEntry = trim($actionEntry);
                if ($actionEntry === '') {
                    continue;
                }

                if (strpos($actionEntry, ':') !== false) {
                    [$actionName, $actionParam] = explode(':', $actionEntry, 2);
                    $actions[] = new Action(trim($actionName), trim($actionParam));
                } else {
                    $actions[] = new Action(trim($actionEntry), null);
                }
            }
        }

        $rule = new Rule($variablesList, $operator, $actions);

        return $rule;
    }
}
