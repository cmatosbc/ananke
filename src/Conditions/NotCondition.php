<?php

namespace Ananke\Conditions;

/**
 * Decorator that negates another condition's result.
 */
class NotCondition extends AbstractCondition
{
    private ConditionInterface $condition;

    /**
     * @param ConditionInterface $condition The condition to negate
     */
    public function __construct(ConditionInterface $condition)
    {
        parent::__construct("not_{$condition->getName()}");
        $this->condition = $condition;
    }

    public function evaluate(): bool
    {
        return !$this->condition->evaluate();
    }
}
