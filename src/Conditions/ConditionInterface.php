<?php

namespace Ananke\Conditions;

/**
 * Interface for service conditions.
 * 
 * Conditions are used to determine whether a service can be instantiated.
 * They can be simple callables or complex compositions using decorators.
 */
interface ConditionInterface
{
    /**
     * Evaluate the condition.
     *
     * @return bool True if the condition is met, false otherwise
     */
    public function evaluate(): bool;

    /**
     * Get a unique identifier for this condition.
     *
     * @return string Condition identifier
     */
    public function getName(): string;
}
