<?php

namespace Ananke\Traits;

trait SingletonServiceTrait
{
    /** @var array<string, object> Singleton instances storage */
    private array $singletons = [];

    /**
     * Register a service as singleton
     *
     * @param string $serviceName Name of the service
     * @return void
     */
    public function registerAsSingleton(string $serviceName): void
    {
        if (!isset($this->singletons[$serviceName])) {
            $this->singletons[$serviceName] = null;
        }
    }

    /**
     * Check if a service is registered as singleton
     *
     * @param string $serviceName Name of the service
     * @return bool
     */
    public function isSingleton(string $serviceName): bool
    {
        return array_key_exists($serviceName, $this->singletons);
    }

    /**
     * Get or create a singleton instance
     *
     * @param string $serviceName Name of the service
     * @param callable $factory Factory function to create the instance if needed
     * @return object
     */
    protected function getSingletonInstance(string $serviceName, callable $factory): object
    {
        if (!isset($this->singletons[$serviceName])) {
            $this->singletons[$serviceName] = $factory();
        }
        
        return $this->singletons[$serviceName];
    }

    /**
     * Clear all singleton instances
     *
     * @return void
     */
    public function clearSingletons(): void
    {
        $this->singletons = [];
    }
}
