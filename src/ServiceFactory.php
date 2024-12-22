<?php

namespace Ananke;

use Ananke\Exceptions\ServiceNotFoundException;
use Ananke\Exceptions\ClassNotFoundException;

class ServiceFactory
{
    /** @var array<string, string> Service name to class name mapping */
    private array $services = [];

    /** @var array<string, callable> Condition name to validation function mapping */
    private array $conditions = [];

    /** @var array<string, string> Service name to condition name mapping */
    private array $serviceConditions = [];

    /** @var array<string, array> Service name to constructor parameters mapping */
    private array $parameters = [];

    /**
     * Register a service with its class name and optional parameters
     */
    public function register(string $serviceName, string $className, array $parameters = []): void
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException("Class not found: $className");
        }

        $this->services[$serviceName] = $className;
        $this->parameters[$serviceName] = $parameters;
    }

    /**
     * Register a condition with a validation function
     */
    public function registerCondition(string $conditionName, callable $validator): void
    {
        $this->conditions[$conditionName] = $validator;
    }

    /**
     * Associate a condition with a service
     */
    public function associateCondition(string $serviceName, string $conditionName): void
    {
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Service not found: $serviceName");
        }

        if (!isset($this->conditions[$conditionName])) {
            throw new \InvalidArgumentException("Condition not found: $conditionName");
        }

        $this->serviceConditions[$serviceName] = $conditionName;
    }

    /**
     * Create an instance of a registered service if its condition (if any) is met
     * 
     * @throws ServiceNotFoundException
     * @throws ClassNotFoundException
     * @throws \InvalidArgumentException
     */
    public function create(string $serviceName): object
    {
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Service not found: $serviceName");
        }

        // Check if service has an associated condition
        if (isset($this->serviceConditions[$serviceName])) {
            $conditionName = $this->serviceConditions[$serviceName];
            $validator = $this->conditions[$conditionName];

            // Evaluate the condition
            $result = match($validator()) {
                true => true,
                false => throw new \InvalidArgumentException("Condition not met for service: $serviceName"),
                default => throw new \InvalidArgumentException("Invalid condition result for service: $serviceName")
            };
        }

        $className = $this->services[$serviceName];
        return new $className(...($this->parameters[$serviceName] ?? []));
    }

    /**
     * Check if a service exists and its condition (if any) is met
     */
    public function has(string $serviceName): bool
    {
        if (!isset($this->services[$serviceName])) {
            return false;
        }

        // If service has a condition, check if it's met
        if (isset($this->serviceConditions[$serviceName])) {
            $conditionName = $this->serviceConditions[$serviceName];
            $validator = $this->conditions[$conditionName];
            
            return (bool) $validator();
        }

        return true;
    }
}
