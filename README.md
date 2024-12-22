# Ananke

A flexible PHP service container that supports conditional service instantiation. This package allows you to register services with conditions that must be met before the service can be instantiated.

## Features

- **Service Registration**: Register classes with their dependencies and constructor parameters
- **Conditional Creation**: Define conditions that must be met before services can be instantiated
- **Flexible Conditions**: Use any callable that returns a boolean as a condition
- **Runtime Validation**: Services are only created when their conditions are satisfied
- **Type Safety**: Full PHP 8.0+ type hints and return type declarations

## Installation

```bash
composer require cmatosbc/ananke
```

## Basic Usage

```php
use Ananke\ServiceFactory;

$factory = new ServiceFactory();

// Register a service with constructor parameters
$factory->register('logger', Logger::class, ['debug']);

// Register a condition
$factory->registerCondition('is-development', fn() => getenv('APP_ENV') === 'development');

// Associate condition with service
$factory->associateCondition('logger', 'is-development');

// Create service (only works in development)
if ($factory->has('logger')) {
    $logger = $factory->create('logger');
}
```

## Real-World Use Cases

### 1. Environment-Specific Services

Control which services are available based on the application environment:

```php
// Register services
$factory->register('debugbar', DebugBar::class);
$factory->register('profiler', Profiler::class);
$factory->register('logger', VerboseLogger::class);

// Register environment condition
$factory->registerCondition('is-development', function() {
    return getenv('APP_ENV') === 'development';
});

// Only allow debug tools in development
$factory->associateCondition('debugbar', 'is-development');
$factory->associateCondition('profiler', 'is-development');
$factory->associateCondition('logger', 'is-development');

// In production, these won't be created
$debugbar = $factory->create('debugbar'); // Throws exception in production
```

### 2. Feature Flags and A/B Testing

Implement feature flags or A/B testing by conditionally creating different service implementations:

```php
// Register different UI implementations
$factory->register('checkout.old', OldCheckoutProcess::class);
$factory->register('checkout.new', NewCheckoutProcess::class);

// Register feature flag condition
$factory->registerCondition('new-checkout-enabled', function() {
    return FeatureFlags::isEnabled('new-checkout') || 
           ABTest::userInGroup('new-checkout');
});

// Use new checkout only when feature is enabled
$factory->associateCondition('checkout.new', 'new-checkout-enabled');

// Get appropriate checkout implementation
$checkout = $factory->has('checkout.new') 
    ? $factory->create('checkout.new')
    : $factory->create('checkout.old');
```

### 3. Database Connection Management

Ensure database-dependent services are only created when a connection is available:

```php
// Register database-dependent services
$factory->register('user.repository', UserRepository::class);
$factory->register('order.repository', OrderRepository::class);
$factory->register('cache.database', DatabaseCache::class);

// Register connection checker
$factory->registerCondition('db-connected', function() {
    try {
        return Database::getInstance()->isConnected();
    } catch (ConnectionException $e) {
        return false;
    }
});

// Ensure repositories only work with database connection
$factory->associateCondition('user.repository', 'db-connected');
$factory->associateCondition('order.repository', 'db-connected');
$factory->associateCondition('cache.database', 'db-connected');

// Safely create repository
if ($factory->has('user.repository')) {
    $users = $factory->create('user.repository');
} else {
    // Fall back to offline mode or throw exception
}
```

### 4. License-Based Feature Access

Control access to premium features based on user licenses:

```php
// Register feature implementations
$factory->register('export.basic', BasicExporter::class);
$factory->register('export.advanced', AdvancedExporter::class);
$factory->register('report.generator', ReportGenerator::class);
$factory->register('ai.assistant', AIAssistant::class);

// Register license checker
$factory->registerCondition('has-premium', function() {
    return License::getCurrentPlan()->isPremium();
});

$factory->registerCondition('has-enterprise', function() {
    return License::getCurrentPlan()->isEnterprise();
});

// Associate features with license levels
$factory->associateCondition('export.advanced', 'has-premium');
$factory->associateCondition('report.generator', 'has-premium');
$factory->associateCondition('ai.assistant', 'has-enterprise');

// Create appropriate exporter based on license
$exporter = $factory->has('export.advanced')
    ? $factory->create('export.advanced')
    : $factory->create('export.basic');
```

## Error Handling

The factory throws different exceptions based on the error:

- `ServiceNotFoundException`: When trying to create a non-existent service
- `ClassNotFoundException`: When the service class doesn't exist
- `InvalidArgumentException`: When a condition is not met or invalid

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.