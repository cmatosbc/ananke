<?php

namespace Ananke\Tests;

use Ananke\ServiceFactory;
use Ananke\Exceptions\ServiceNotFoundException;
use Ananke\Exceptions\ClassNotFoundException;
use PHPUnit\Framework\TestCase;

class Logger {
    private string $level;
    
    public function __construct(string $level = 'info') {
        $this->level = $level;
        printf("\n    Created Logger with level: %s", $level);
    }
    
    public function getLevel(): string {
        return $this->level;
    }
}

class Database {
    private bool $isConnected = false;
    
    public function __construct() {
        printf("\n    Created Database instance");
    }
    
    public function connect(): void {
        $this->isConnected = true;
    }
    
    public function isConnected(): bool {
        return $this->isConnected;
    }
}

class ServiceFactoryTest extends TestCase
{
    private ServiceFactory $factory;
    private bool $isDevelopment = true;
    private bool $isDbConnected = false;

    protected function setUp(): void
    {
        $this->factory = new ServiceFactory();
        printf("\n\nSetting up new ServiceFactory instance");
    }

    /**
     * @test
     */
    public function itShouldRegisterAndCreateServices(): void
    {
        printf("\n\nTesting basic service registration");

        // Register services with different parameters
        $this->factory->register('logger.debug', Logger::class, ['debug']);
        $this->factory->register('logger.error', Logger::class, ['error']);
        printf("\n    Registered logger services with different levels");

        // Create and verify instances
        $debugLogger = $this->factory->create('logger.debug');
        $this->assertInstanceOf(Logger::class, $debugLogger);
        $this->assertEquals('debug', $debugLogger->getLevel());
        printf("\n    Successfully created debug logger");

        $errorLogger = $this->factory->create('logger.error');
        $this->assertInstanceOf(Logger::class, $errorLogger);
        $this->assertEquals('error', $errorLogger->getLevel());
        printf("\n    Successfully created error logger");
    }

    /**
     * @test
     */
    public function itShouldRegisterAndValidateConditions(): void
    {
        printf("\n\nTesting condition registration");

        // Register conditions
        $this->factory->registerCondition('dev-only', fn() => $this->isDevelopment);
        $this->factory->registerCondition('db-connected', fn() => $this->isDbConnected);
        printf("\n    Registered development and database conditions");

        // Register services
        $this->factory->register('logger.debug', Logger::class, ['debug']);
        $this->factory->register('database', Database::class);
        printf("\n    Registered services");

        // Associate conditions
        $this->factory->associateCondition('logger.debug', 'dev-only');
        $this->factory->associateCondition('database', 'db-connected');
        printf("\n    Associated conditions with services");

        // Test dev-only condition (should succeed)
        $this->assertTrue($this->factory->has('logger.debug'), 'Debug logger should be available in development');
        $debugLogger = $this->factory->create('logger.debug');
        $this->assertInstanceOf(Logger::class, $debugLogger);
        printf("\n    Successfully created dev-only logger");

        // Test db-connected condition (should fail)
        $this->assertFalse($this->factory->has('database'), 'Database should not be available when disconnected');
        $this->expectException(\InvalidArgumentException::class);
        printf("\n    Attempting to create database instance (should fail)...");
        $this->factory->create('database');
    }

    /**
     * @test
     */
    public function itShouldRespectConditionsForServiceCreation(): void
    {
        printf("\n\nTesting condition-based service creation");

        // Register service and condition
        $this->factory->register('database', Database::class);
        $this->factory->registerCondition('db-connected', fn() => $this->isDbConnected);
        $this->factory->associateCondition('database', 'db-connected');
        printf("\n    Registered database service with connection condition");

        // Try to create when condition is false
        $this->assertFalse($this->factory->has('database'), 'Database should not be available when disconnected');
        printf("\n    Verified database is not available when disconnected");

        // Change condition to true
        $this->isDbConnected = true;
        printf("\n    Connected to database");

        // Try to create when condition is true
        $this->assertTrue($this->factory->has('database'), 'Database should be available when connected');
        $db = $this->factory->create('database');
        $this->assertInstanceOf(Database::class, $db);
        printf("\n    Successfully created database instance after connecting");
    }

    /**
     * @test
     */
    public function itShouldHandleErrorsGracefully(): void
    {
        printf("\n\nTesting error handling");

        // Test non-existent service
        $this->assertFalse($this->factory->has('non.existent'));
        printf("\n    Verified non-existent service is not available");

        $this->expectException(ServiceNotFoundException::class);
        printf("\n    Attempting to create non-existent service...");
        $this->factory->create('non.existent');
    }
}
