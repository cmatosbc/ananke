<?php

namespace Ananke\Traits;

trait ServiceTypeTrait
{
    /** @var array<string, string> Service name to service type mapping */
    protected array $serviceTypes = [];

    /**
     * Change service type between singleton and prototype
     *
     * @param string $serviceName Name of the service
     * @param string $type New service type ('singleton' or 'prototype')
     * @throws \InvalidArgumentException When invalid type is provided
     * @return void
     */
    public function changeServiceType(string $serviceName, string $type): void
    {
        if (!in_array($type, ['singleton', 'prototype'])) {
            throw new \InvalidArgumentException("Invalid service type: $type");
        }

        if ($type === 'prototype') {
            $this->registerAsPrototype($serviceName);
        } else {
            $this->registerAsSingleton($serviceName);
        }

        $this->serviceTypes[$serviceName] = $type;
    }

    /**
     * Get the current type of a service
     *
     * @param string $serviceName Name of the service
     * @return string
     */
    public function getServiceType(string $serviceName): string
    {
        return $this->serviceTypes[$serviceName] ?? 'prototype';
    }
}
