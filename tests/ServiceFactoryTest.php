<?php

namespace Ananke\Tests;

use Ananke\ServiceFactory;
use Ananke\Exceptions\ServiceNotFoundException;
use Ananke\Exceptions\ClassNotFoundException;
use PHPUnit\Framework\TestCase;

class PremiumFeature {
    private bool $enabled;
    
    public function __construct(bool $enabled = true) {
        $this->enabled = $enabled;
        printf("\n    Created PremiumFeature (enabled: %s)", $enabled ? 'yes' : 'no');
    }
    
    public function isEnabled(): bool {
        return $this->enabled;
    }
}

class ServiceFactoryTest extends TestCase
{
    private ServiceFactory $factory;
    private bool $isPremium = false;
    private bool $isFeatureEnabled = false;
    private bool $hasValidLicense = false;

    protected function setUp(): void
    {
        $this->factory = new ServiceFactory();
        printf("\n\n🏭 Setting up new test case");
    }

    private function printConditionState(string $message): void
    {
        printf("\n    📊 Current State:");
        printf("\n       • Premium Status: %s", $this->isPremium ? '✅' : '❌');
        printf("\n       • Feature Flag: %s", $this->isFeatureEnabled ? '✅' : '❌');
        printf("\n       • Valid License: %s", $this->hasValidLicense ? '✅' : '❌');
        if ($message) {
            printf("\n    ℹ️  %s", $message);
        }
    }

    /**
     * @test
     */
    public function itShouldRegisterAndCreateBasicServices(): void
    {
        printf("\n\n🧪 Test: Basic Service Registration");
        
        // Register a simple service
        $this->factory->register('feature', PremiumFeature::class, [true]);
        printf("\n    ✅ Registered basic feature service");

        // Create and verify instance
        $feature = $this->factory->create('feature');
        $this->assertInstanceOf(PremiumFeature::class, $feature);
        $this->assertTrue($feature->isEnabled());
        printf("\n    ✨ Successfully created basic feature without conditions");
    }

    /**
     * @test
     */
    public function itShouldHandleMultipleConditions(): void
    {
        printf("\n\n🧪 Test: Multiple Conditions");

        // Register service
        $this->factory->register('premium.feature', PremiumFeature::class, [true]);
        printf("\n    ✅ Registered premium feature service");

        // Register all conditions
        $this->factory->registerCondition('is-premium', fn() => $this->isPremium);
        $this->factory->registerCondition('feature-enabled', fn() => $this->isFeatureEnabled);
        $this->factory->registerCondition('has-license', fn() => $this->hasValidLicense);
        printf("\n    ✅ Registered all conditions");

        // Associate all conditions
        $this->factory->associateCondition('premium.feature', 'is-premium');
        $this->factory->associateCondition('premium.feature', 'feature-enabled');
        $this->factory->associateCondition('premium.feature', 'has-license');
        printf("\n    ✅ Associated all conditions with premium feature");

        // Test: No conditions met
        $this->printConditionState("Testing with no conditions met");
        $this->assertFalse(
            $this->factory->has('premium.feature'),
            'Feature should not be available when no conditions are met'
        );
        printf("\n    ✅ Verified feature is not available with no conditions met");

        // Test: Only premium status
        $this->isPremium = true;
        $this->printConditionState("Testing with only premium status");
        $this->assertFalse(
            $this->factory->has('premium.feature'),
            'Feature should not be available with only premium status'
        );
        printf("\n    ✅ Verified feature is not available with only premium status");

        // Test: Premium status and feature flag
        $this->isFeatureEnabled = true;
        $this->printConditionState("Testing with premium status and feature flag");
        $this->assertFalse(
            $this->factory->has('premium.feature'),
            'Feature should not be available without license'
        );
        printf("\n    ✅ Verified feature is not available without license");

        // Test: All conditions met
        $this->hasValidLicense = true;
        $this->printConditionState("Testing with all conditions met");
        $this->assertTrue(
            $this->factory->has('premium.feature'),
            'Feature should be available when all conditions are met'
        );
        printf("\n    ✅ Verified feature is available with all conditions met");

        // Test: Create instance with all conditions met
        printf("\n    🔨 Creating feature instance...");
        $feature = $this->factory->create('premium.feature');
        $this->assertInstanceOf(PremiumFeature::class, $feature);
        $this->assertTrue($feature->isEnabled());
        printf("\n    ✨ Successfully created feature instance");

        // Test: Failure when one condition becomes false
        $this->isFeatureEnabled = false;
        $this->printConditionState("Testing after disabling feature flag");
        $this->assertFalse(
            $this->factory->has('premium.feature'),
            'Feature should not be available when any condition fails'
        );
        printf("\n    ✅ Verified feature is not available after condition failure");
        
        // Test: Exception when creating with failed condition
        printf("\n    ⚠️  Attempting to create feature with failed condition...");
        try {
            $this->factory->create('premium.feature');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                "Condition 'feature-enabled' not met for service: premium.feature",
                $e->getMessage()
            );
            printf("\n    ✅ Correctly caught exception: %s", $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function itShouldHandleServiceNotFound(): void
    {
        printf("\n\n🧪 Test: Service Not Found");
        
        printf("\n    ⚠️  Attempting to create non-existent service...");
        $this->expectException(ServiceNotFoundException::class);
        $this->factory->create('non.existent');
    }

    /**
     * @test
     */
    public function itShouldHandleClassNotFound(): void
    {
        printf("\n\n🧪 Test: Class Not Found");
        
        printf("\n    ⚠️  Attempting to register non-existent class...");
        $this->expectException(ClassNotFoundException::class);
        $this->factory->register('invalid', 'NonExistentClass');
    }

    /**
     * @test
     */
    public function itShouldHandleInvalidCondition(): void
    {
        printf("\n\n🧪 Test: Invalid Condition");
        
        // Register service
        $this->factory->register('feature', PremiumFeature::class);
        printf("\n    ✅ Registered feature service");

        printf("\n    ⚠️  Attempting to associate non-existent condition...");
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->associateCondition('feature', 'non-existent-condition');
    }
}
