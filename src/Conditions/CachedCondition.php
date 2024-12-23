<?php

namespace Ananke\Conditions;

/**
 * Decorator that caches another condition's result for a specified duration.
 */
class CachedCondition extends AbstractCondition
{
    private ConditionInterface $condition;
    private ?bool $cachedResult = null;
    private ?int $cachedAt = null;
    private int $ttl;

    /**
     * @param ConditionInterface $condition The condition to cache
     * @param int $ttl Time to live in seconds
     */
    public function __construct(ConditionInterface $condition, int $ttl)
    {
        parent::__construct("cached_{$condition->getName()}");
        $this->condition = $condition;
        $this->ttl = $ttl;
    }

    public function evaluate(): bool
    {
        $now = time();

        // If we have a cached result and it hasn't expired
        if (
            $this->cachedResult !== null &&
            $this->cachedAt !== null &&
            $now - $this->cachedAt < $this->ttl
        ) {
            return $this->cachedResult;
        }

        // Evaluate and cache the result
        $this->cachedResult = $this->condition->evaluate();
        $this->cachedAt = $now;

        return $this->cachedResult;
    }

    /**
     * Clear the cached result.
     */
    public function clearCache(): void
    {
        $this->cachedResult = null;
        $this->cachedAt = null;
    }
}
