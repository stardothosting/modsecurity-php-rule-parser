<?php

namespace Stardothosting\ModSecurity\Model;

/**
 * Represents a parsed ModSecurity rule, including variables, operator, actions, and chained rules.
 */
class Rule implements \JsonSerializable
{
    /**
     * @var Variable[] List of variables for this rule
     */
    public array $variables = [];

    /**
     * @var Operator|null The operator for this rule
     */
    public ?Operator $operator = null;

    /**
     * @var Action[] List of actions for this rule
     */
    public array $actions = [];

    /**
     * @var Rule[] List of chained rules (if any)
     */
    public array $chainedRules = [];

    /**
     * Rule constructor.
     *
     * @param Variable[] $variables
     * @param Operator|null $operator
     * @param Action[] $actions
     */
    public function __construct(array $variables, ?Operator $operator, array $actions)
    {
        $this->variables = $variables;
        $this->operator = $operator;
        $this->actions = $actions;
    }

    /**
     * Add a chained rule to this rule.
     *
     * @param Rule $rule
     */
    public function addChainedRule(Rule $rule): void
    {
        $this->chainedRules[] = $rule;
    }

    /**
     * Convert the rule (and chained rules) to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'variables' => array_map(fn($v) => $v->toArray(), $this->variables),
            'operator' => $this->operator?->toArray(),
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            'chained_rules' => array_map(fn($r) => $r->toArray(), $this->chainedRules),
        ];
    }

    public function jsonSerialize()
    {
        return [
            'variables' => $this->variables,
            'operator' => $this->operator,
            'actions' => $this->actions,
            'chained_rules' => $this->chained_rules,
        ];
    }
}
