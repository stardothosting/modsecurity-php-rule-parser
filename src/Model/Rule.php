<?php

namespace ModSecurity\Model;

class Rule
{
    public array $variables = [];
    public ?Operator $operator = null;
    public array $actions = [];
    public array $chainedRules = [];

    public function __construct(array $variables, ?Operator $operator, array $actions)
    {
        $this->variables = $variables;
        $this->operator = $operator;
        $this->actions = $actions;
    }

    public function addChainedRule(Rule $rule): void
    {
        $this->chainedRules[] = $rule;
    }

    public function toArray(): array
    {
        return [
            'variables' => array_map(fn($v) => $v->toArray(), $this->variables),
            'operator' => $this->operator?->toArray(),
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            'chained_rules' => array_map(fn($r) => $r->toArray(), $this->chainedRules),
        ];
    }
}
