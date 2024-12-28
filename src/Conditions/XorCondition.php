<?php

namespace Ananke\Conditions;

/**
 * Decorator that combines multiple conditions with XOR logic.
 */
class XorCondition extends AbstractCondition
{
    /** @var ConditionInterface[] */
    private array $conditions;

    /**
     * @param ConditionInterface[] $conditions List of conditions to combine
     */
    public function __construct(array $conditions)
    {
        $names = array_map(fn($c) => $c->getName(), $conditions);
        parent::__construct('xor_' . implode('_', $names));
        $this->conditions = $conditions;
    }

    public function evaluate(): bool
    {
        $trueCount = 0;

        foreach ($this->conditions as $condition) {
            if ($condition->evaluate()) {
                $trueCount++;
            }
        }

        return $trueCount === 1;
    }
}
