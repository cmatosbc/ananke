<?php

namespace Ananke\Conditions;

/**
 * Basic condition implementation that wraps a callable.
 */
class CallableCondition implements ConditionInterface
{
    private string $name;
    private \Closure $validator;

    /**
     * @param string $name Unique identifier for this condition
     * @param callable $validator Function that returns bool when evaluated
     */
    public function __construct(string $name, callable $validator)
    {
        $this->name = $name;
        $this->validator = \Closure::fromCallable($validator);
    }

    public function evaluate(): bool
    {
        return ($this->validator)();
    }

    public function getName(): string
    {
        return $this->name;
    }
}
