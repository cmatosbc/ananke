<?php

namespace Ananke;

use Ananke\Exceptions\ServiceNotFoundException;
use Ananke\Exceptions\ClassNotFoundException;
use Ananke\Conditions\ConditionInterface;
use Ananke\Conditions\CallableCondition;
use Ananke\Traits\SingletonServiceTrait;
use Ananke\Traits\PrototypeServiceTrait;
use Ananke\Traits\ServiceTypeTrait;

/**
 * A flexible service container that supports conditional service instantiation.
 *
 * This container allows registering services with multiple conditions that must be met
 * before the service can be instantiated. Each service can have zero or more conditions,
 * and all conditions must evaluate to true for the service to be created.
 */
class ServiceFactory
{
    use ServiceTypeTrait;
    use SingletonServiceTrait;
    use PrototypeServiceTrait;
    
    /** @var array<string, string> Service name to class name mapping */
    private array $services = [];

    /** @var array<string, ConditionInterface> Condition name to condition object mapping */
    private array $conditions = [];

    /** @var array<string, array<string>> Service name to condition names mapping */
    private array $serviceConditions = [];

    /** @var array<string, array> Service name to constructor parameters mapping */
    private array $parameters = [];

    /**
     * Register a service with its class name and optional parameters.
     *
     * @param string $serviceName Unique identifier for the service
     * @param string $className Fully qualified class name that exists
     * @param array $parameters Optional constructor parameters for the service
     * @param string $type Service type (singleton or prototype)
     * @throws ClassNotFoundException When the class does not exist
     */
    public function register(
        string $serviceName, 
        string $className, 
        array $parameters = [],
        string $type = 'prototype'
    ): void {
        if (!class_exists($className)) {
            throw new ClassNotFoundException("Class not found: $className");
        }

        $this->services[$serviceName] = $className;
        $this->parameters[$serviceName] = $parameters;
        $this->setServiceType($serviceName, $type);
    }

    /**
     * Register a condition with a validation function or condition object.
     *
     * @param string $conditionName Unique identifier for the condition
     * @param callable|ConditionInterface $validator Function that returns bool when condition is evaluated
     */
    public function registerCondition(string $conditionName, callable|ConditionInterface $validator): void
    {
        if ($validator instanceof ConditionInterface) {
            $this->conditions[$conditionName] = $validator;
        } else {
            $this->conditions[$conditionName] = new CallableCondition($conditionName, $validator);
        }
    }

    /**
     * Associate a condition with a service.
     *
     * Multiple conditions can be associated with the same service. All conditions
     * must evaluate to true for the service to be created.
     *
     * @param string $serviceName Name of a registered service
     * @param string $conditionName Name of a registered condition
     * @throws ServiceNotFoundException When service is not registered
     * @throws \InvalidArgumentException When condition is not registered
     */
    public function associateCondition(string $serviceName, string $conditionName): void
    {
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Service not found: $serviceName");
        }

        if (!isset($this->conditions[$conditionName])) {
            throw new \InvalidArgumentException("Condition not found: $conditionName");
        }

        $this->serviceConditions[$serviceName][] = $conditionName;
    }

    /**
     * Evaluates all conditions for a service.
     *
     * @param string $serviceName Name of the service to evaluate conditions for
     * @throws \InvalidArgumentException When a condition is not met or returns invalid result
     * @return \Generator<bool> Yields true for each satisfied condition
     */
    private function evaluateConditions(string $serviceName): \Generator
    {
        if (!isset($this->serviceConditions[$serviceName])) {
            yield true;
            return;
        }

        foreach ($this->serviceConditions[$serviceName] as $conditionName) {
            $condition = $this->conditions[$conditionName];
            $result = $condition->evaluate();

            yield match ($result) {
                true => true,
                false => throw new \InvalidArgumentException(
                    "Condition '$conditionName' not met for service: $serviceName"
                ),
                default => throw new \InvalidArgumentException(
                    "Invalid result for condition '$conditionName' on service: $serviceName"
                )
            };
        }
    }

    /**
     * Create an instance of a registered service if all its conditions are met.
     *
     * @param string $serviceName Name of the service to create
     * @throws ServiceNotFoundException When service is not registered
     * @throws \InvalidArgumentException When any condition is not met
     * @return object Instance of the requested service
     */
    public function create(string $serviceName): object
    {
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Service not found: $serviceName");
        }

        // Evaluate all conditions
        foreach ($this->evaluateConditions($serviceName) as $result) {
            // Just iterate through all conditions
            // Any failed condition will throw an exception
        }

        if ($this->isSingleton($serviceName)) {
            if (!isset($this->singletons[$serviceName])) {
                $this->singletons[$serviceName] = $this->createInstance($serviceName);
            }
            return $this->singletons[$serviceName];
        }

        return $this->createInstance($serviceName);
    }

    /**
     * Create a new instance of a service
     *
     * @param string $serviceName Name of the service
     * @return object
     */
    private function createInstance(string $serviceName): object
    {
        $className = $this->services[$serviceName];
        return new $className(...($this->parameters[$serviceName] ?? []));
    }

    /**
     * Check if a service exists and all its conditions are met.
     *
     * @param string $serviceName Name of the service to check
     * @return bool True if service exists and all conditions are met
     */
    public function has(string $serviceName): bool
    {
        if (!isset($this->services[$serviceName])) {
            return false;
        }

        // Check all conditions
        try {
            foreach ($this->evaluateConditions($serviceName) as $result) {
                if (!$result) {
                    return false;
                }
            }
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Set the service type during registration
     *
     * @param string $serviceName Name of the service
     * @param string $type Service type (singleton or prototype)
     * @throws \InvalidArgumentException When invalid type is provided
     */
    private function setServiceType(string $serviceName, string $type): void
    {
        if (!in_array($type, ['singleton', 'prototype'])) {
            throw new \InvalidArgumentException("Invalid service type: $type");
        }

        if ($type === 'singleton') {
            $this->registerAsSingleton($serviceName);
        } else {
            $this->registerAsPrototype($serviceName);
        }
    }
}
