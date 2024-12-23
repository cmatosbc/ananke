<?php

namespace Ananke\Conditions;

/**
 * Base class for conditions providing common functionality.
 */
abstract class AbstractCondition implements ConditionInterface
{
    protected string $name;

    /**
     * @param string $name Unique identifier for this condition
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
