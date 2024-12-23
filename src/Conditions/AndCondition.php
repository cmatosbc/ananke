<?php

namespace Ananke\Conditions;

/**
 * Decorator that combines multiple conditions with AND logic.
 */
class AndCondition extends AbstractCondition
{
    /** @var ConditionInterface[] */
    private array $conditions;

    /**
     * @param ConditionInterface[] $conditions List of conditions to combine
     */
    public function __construct(array $conditions)
    {
        $names = array_map(fn($c) => $c->getName(), $conditions);
        parent::__construct('and_' . implode('_', $names));
        $this->conditions = $conditions;
    }

    public function evaluate(): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->evaluate()) {
                return false;
            }
        }
        return true;
    }
}
