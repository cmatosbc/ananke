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

    /** @var array<string, array<string>> Service name to condition names mapping */
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

        $this->serviceConditions[$serviceName][] = $conditionName;
    }

    /**
     * Evaluates all conditions for a service
     *
     * @throws \InvalidArgumentException When a condition is not met
     */
    private function evaluateConditions(string $serviceName): \Generator
    {
        if (!isset($this->serviceConditions[$serviceName])) {
            yield true;
            return;
        }

        foreach ($this->serviceConditions[$serviceName] as $conditionName) {
            $validator = $this->conditions[$conditionName];
            $result = $validator();

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
     * Create an instance of a registered service if all its conditions are met
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

        // Evaluate all conditions
        foreach ($this->evaluateConditions($serviceName) as $result) {
            // Just iterate through all conditions
            // Any failed condition will throw an exception
        }

        $className = $this->services[$serviceName];
        return new $className(...($this->parameters[$serviceName] ?? []));
    }

    /**
     * Check if a service exists and all its conditions are met
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
}
