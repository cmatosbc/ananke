<?php

namespace Ananke\Traits;

use Ananke\Traits\ServiceTypeTrait;

trait PrototypeServiceTrait
{
    use ServiceTypeTrait;

    /**
     * Register a service as prototype
     *
     * @param string $serviceName Name of the service
     * @return void
     */
    public function registerAsPrototype(string $serviceName): void
    {
        if (array_key_exists($serviceName, $this->singletons)) {
            unset($this->singletons[$serviceName]);
        }
    }

    /**
     * Check if a service is registered as prototype
     *
     * @param string $serviceName Name of the service
     * @return bool
     */
    public function isPrototype(string $serviceName): bool
    {
        return !array_key_exists($serviceName, $this->singletons);
    }
}
