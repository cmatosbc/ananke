<?php

namespace Ananke\Tests;

use PHPUnit\Framework\TestCase;
use Ananke\ServiceFactory;
use Ananke\Tests\Fixtures\SimpleService;
use Ananke\Tests\Fixtures\ComplexService;

class ServiceTypeTest extends TestCase
{
    private ServiceFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ServiceFactory();
        printf("\n\n🏭 Setting up new service type test");
    }

    /**
     * @test
     */
    public function testDefaultServiceTypeIsPrototype(): void
    {
        printf("\n\n🧪 Test: Default Service Type");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered service");
        
        $this->assertTrue($this->factory->isPrototype('service'));
        $this->assertFalse($this->factory->isSingleton('service'));
        printf("\n    ℹ️  Service is prototype by default");
    }

    /**
     * @test
     */
    public function testRegisterAsSingleton(): void
    {
        printf("\n\n🧪 Test: Singleton Registration");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered service");
        
        $this->factory->registerAsSingleton('service');
        printf("\n    ✅ Converted to singleton");
        
        $this->assertTrue($this->factory->isSingleton('service'), "Service should be singleton");
        $this->assertFalse($this->factory->isPrototype('service'), "Service should not be prototype");
    }

    /**
     * @test
     */
    public function testRegisterAsPrototype(): void
    {
        printf("\n\n🧪 Test: Prototype Registration");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered service");
        
        $this->factory->registerAsSingleton('service');
        printf("\n    ✅ Converted to singleton");
        
        $this->factory->registerAsPrototype('service');
        printf("\n    ✅ Converted back to prototype");
        
        $this->assertTrue($this->factory->isPrototype('service'), "Service should be prototype");
        $this->assertFalse($this->factory->isSingleton('service'), "Service should not be singleton");
    }

    /**
     * @test
     */
    public function testSingletonReturnsSameInstance(): void
    {
        printf("\n\n🧪 Test: Singleton Instance Check");
        
        $this->factory->register('service', SimpleService::class);
        $this->factory->registerAsSingleton('service');
        printf("\n    ✅ Registered singleton service");
        
        $instance1 = $this->factory->create('service');
        printf("\n    ✅ Created first instance (ID: %s)", $instance1->getId());
        
        $instance2 = $this->factory->create('service');
        printf("\n    ✅ Created second instance (ID: %s)", $instance2->getId());
        
        $this->assertSame($instance1, $instance2, "Singleton should return same instance");
        printf("\n    ℹ️  Both instances are identical");
    }

    /**
     * @test
     */
    public function testPrototypeReturnsNewInstance(): void
    {
        printf("\n\n🧪 Test: Prototype Instance Check");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered prototype service");
        
        $instance1 = $this->factory->create('service');
        printf("\n    ✅ Created first instance (ID: %s)", $instance1->getId());
        
        $instance2 = $this->factory->create('service');
        printf("\n    ✅ Created second instance (ID: %s)", $instance2->getId());
        
        $this->assertNotSame($instance1, $instance2, "Prototype should return different instances");
        printf("\n    ℹ️  Instances are different as expected");
    }

    /**
     * @test
     */
    public function testChangeServiceType(): void
    {
        printf("\n\n🧪 Test: Service Type Change");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered service");
        
        // Change to singleton
        $this->factory->changeServiceType('service', 'singleton');
        printf("\n    ✅ Changed to singleton");
        $this->assertTrue($this->factory->isSingleton('service'), "Service should be singleton after change");
        
        // Get instances
        $instance1 = $this->factory->create('service');
        $instance2 = $this->factory->create('service');
        $this->assertSame($instance1, $instance2, "Singleton should return same instance");
        printf("\n    ℹ️  Singleton instances are identical (ID: %s)", $instance1->getId());
        
        // Change to prototype
        $this->factory->changeServiceType('service', 'prototype');
        printf("\n    ✅ Changed to prototype");
        $this->assertTrue($this->factory->isPrototype('service'), "Service should be prototype after change");
        
        // Get new instances
        $instance3 = $this->factory->create('service');
        $instance4 = $this->factory->create('service');
        $this->assertNotSame($instance3, $instance4, "Prototype should return different instances");
        printf("\n    ℹ️  Prototype instances are different (IDs: %s, %s)", 
            $instance3->getId(), 
            $instance4->getId()
        );
    }

    /**
     * @test
     */
    public function testClearSingletons(): void
    {
        printf("\n\n🧪 Test: Clear Singletons");
        
        // Register services
        $this->factory->register('service1', SimpleService::class);
        $this->factory->register('service2', ComplexService::class);
        printf("\n    ✅ Registered two services");
        
        // Make them singletons
        $this->factory->registerAsSingleton('service1');
        $this->factory->registerAsSingleton('service2');
        printf("\n    ✅ Converted both to singletons");
        
        // Create initial instances
        $instance1 = $this->factory->create('service1');
        $instance2 = $this->factory->create('service2');
        printf("\n    ℹ️  Created initial instances (IDs: %s, %s)", 
            $instance1->getId(), 
            $instance2->getId()
        );
        
        // Clear singletons
        $this->factory->clearSingletons();
        printf("\n    ✅ Cleared all singletons");
        
        // Get new instances
        $instance3 = $this->factory->create('service1');
        $instance4 = $this->factory->create('service2');
        
        // Verify new instances
        $this->assertNotSame($instance1, $instance3, "Should get new instance after clearing");
        $this->assertNotSame($instance2, $instance4, "Should get new instance after clearing");
        printf("\n    ℹ️  New instances have different IDs: %s, %s", 
            $instance3->getId(), 
            $instance4->getId()
        );
    }

    /**
     * @test
     */
    public function testInvalidServiceTypeThrowsException(): void
    {
        printf("\n\n🧪 Test: Invalid Service Type");
        
        $this->factory->register('service', SimpleService::class);
        printf("\n    ✅ Registered service");
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service type: invalid');
        
        printf("\n    ℹ️  Attempting to set invalid type...");
        $this->factory->changeServiceType('service', 'invalid');
    }
}
