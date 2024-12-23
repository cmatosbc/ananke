# Ananke

A flexible PHP service container that supports conditional service instantiation. This package allows you to register services with multiple conditions that must be met before the service can be instantiated.

## Features

- Register services with their class names and constructor parameters
- Define conditions as callable functions
- Associate multiple conditions with services
- Dynamic service instantiation based on condition evaluation
- Clear error handling with specific exceptions

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

// Register conditions
$factory->registerCondition('is-development', fn() => getenv('APP_ENV') === 'development');
$factory->registerCondition('has-permissions', fn() => is_writable('/var/log'));

// Associate multiple conditions with service
$factory->associateCondition('logger', 'is-development');
$factory->associateCondition('logger', 'has-permissions');

// Create service (only works if ALL conditions are met)
if ($factory->has('logger')) {
    $logger = $factory->create('logger');
}
```

## Multiple Conditions

Services can have multiple conditions that must ALL be satisfied before instantiation:

```php
// Premium feature example
$factory->register('premium.feature', PremiumFeature::class);

// Register all required conditions
$factory->registerCondition('is-premium-user', fn() => $user->hasPremiumSubscription());
$factory->registerCondition('feature-enabled', fn() => $featureFlags->isEnabled('new-feature'));
$factory->registerCondition('has-valid-license', fn() => $license->isValid());

// Associate ALL conditions with the service
$factory->associateCondition('premium.feature', 'is-premium-user');
$factory->associateCondition('premium.feature', 'feature-enabled');
$factory->associateCondition('premium.feature', 'has-valid-license');

// Service will only be created if ALL conditions are met
if ($factory->has('premium.feature')) {
    $feature = $factory->create('premium.feature');
}
```

## Real-World Use Cases

### 1. Environment-Specific Services

Control debug tools based on environment:

```php
$factory->register('debugger', Debugger::class);
$factory->registerCondition('is-development', fn() => getenv('APP_ENV') === 'development');
$factory->registerCondition('debug-enabled', fn() => getenv('APP_DEBUG') === 'true');
$factory->associateCondition('debugger', 'is-development');
$factory->associateCondition('debugger', 'debug-enabled');
```

### 2. Feature Flags and A/B Testing

Implement feature toggles with multiple conditions:

```php
$factory->register('new.ui', NewUIComponent::class);
$factory->registerCondition('feature-enabled', fn() => $featureFlags->isEnabled('new-ui'));
$factory->registerCondition('in-test-group', fn() => $abTest->isInGroup('new-ui-test'));
$factory->registerCondition('supported-browser', fn() => $browser->supportsFeature('grid-layout'));
$factory->associateCondition('new.ui', 'feature-enabled');
$factory->associateCondition('new.ui', 'in-test-group');
$factory->associateCondition('new.ui', 'supported-browser');
```

### 3. Database Connection Management

Safe handling of database-dependent services:

```php
$factory->register('user.repository', UserRepository::class);
$factory->registerCondition('db-connected', fn() => $database->isConnected());
$factory->registerCondition('db-migrated', fn() => $database->isMigrated());
$factory->registerCondition('has-permissions', fn() => $database->hasPermissions('users'));
$factory->associateCondition('user.repository', 'db-connected');
$factory->associateCondition('user.repository', 'db-migrated');
$factory->associateCondition('user.repository', 'has-permissions');
```

### 4. License-Based Feature Access

Control access to premium features:

```php
$factory->register('premium.api', PremiumAPIClient::class);
$factory->registerCondition('has-license', fn() => $license->isValid());
$factory->registerCondition('within-quota', fn() => $usage->isWithinQuota());
$factory->registerCondition('api-available', fn() => $api->isAvailable());
$factory->associateCondition('premium.api', 'has-license');
$factory->associateCondition('premium.api', 'within-quota');
$factory->associateCondition('premium.api', 'api-available');
```

## Error Handling

The service container throws specific exceptions:

- `ServiceNotFoundException`: When trying to create a non-registered service
- `ClassNotFoundException`: When registering a service with a non-existent class
- `InvalidArgumentException`: When a condition is not met or invalid

## Testing

Run the test suite:

```bash
composer test
```

The tests provide detailed output showing the state of conditions and service creation:

```
🧪 Test: Multiple Conditions
    ✅ Registered premium feature service
    ✅ Registered all conditions
    
    📊 Current State:
       • Premium Status: ✅
       • Feature Flag: ✅
       • Valid License: ❌
    ℹ️  Testing with incomplete conditions
    ✅ Verified feature is not available
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-3.0-or-later License - see the LICENSE file for details.