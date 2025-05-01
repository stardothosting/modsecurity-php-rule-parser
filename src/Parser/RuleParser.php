<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Model\Rule;
use Stardothosting\ModSecurity\Model\Action;
use Stardothosting\ModSecurity\Model\Variable;
use Illuminate\Support\Facades\Log;

class RuleParser
{
    public function parse(string $ruleString): Rule
    {
        // Remove leading SecRule and surrounding whitespace
        $ruleString = trim(preg_replace('/^SecRule\s+/i', '', $ruleString));

        // Match pattern: VARIABLES OPERATOR "ACTIONS"
        if (!preg_match('/^(.*?)\s+"?(@[^\s"]+\s+[^\s"]+)"?\s+"([^"]*)"$/', $ruleString, $matches)) {
            throw new \RuntimeException("Failed to parse rule structure: $ruleString");
        }

        [$full, $varString, $operatorString, $actionString] = $matches;

        $variables = $this->parseVariables($varString);
        $operator = $this->parseOperator($operatorString);
        $actions = $this->parseActions($actionString);

        return new Rule($variables, $operator, $actions);
    }

    private function parseVariables(string $input): array
    {
        $parts = explode('|', $input);
        $variables = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ':') !== false) {
                [$name, $key] = explode(':', $part, 2);
                $variables[] = new Variable(trim($name), trim($key));
            } else {
                $variables[] = new Variable(trim($part));
            }
        }

        return $variables;
    }

    private function parseOperator(string $input): array
    {
        $input = trim($input);
        if (preg_match('/^@!?([^\s]+)\s+(.*)$/', $input, $matches)) {
            return [
                'name' => $matches[1],
                'param' => trim($matches[2])
            ];
        }

        return [
            'name' => $input,
            'param' => null
        ];
    }

    private function parseActions(string $actionString): array
    {
        $actions = [];
        $parts = preg_split('/,(?=(?:[^\'\"]|\'[^\']*\'|\"[^\"]*\")*$)/', $actionString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ':') !== false) {
                [$name, $param] = explode(':', $part, 2);
                $actions[] = new Action(trim($name), trim($param, " '"));
            } else {
                $actions[] = new Action(trim($part));
            }
        }

        return $actions;
    }
}