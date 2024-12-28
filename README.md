# Ananke

[![PHP Lint](https://github.com/cmatosbc/ananke/actions/workflows/lint.yml/badge.svg)](https://github.com/cmatosbc/ananke/actions/workflows/lint.yml) [![PHPUnit Tests](https://github.com/cmatosbc/ananke/actions/workflows/phpunit.yml/badge.svg)](https://github.com/cmatosbc/ananke/actions/workflows/phpunit.yml) [![PHP Composer](https://github.com/cmatosbc/ananke/actions/workflows/composer.yml/badge.svg)](https://github.com/cmatosbc/ananke/actions/workflows/composer.yml) [![Latest Stable Version](https://poser.pugx.org/cmatosbc/ananke/v)](https://packagist.org/packages/cmatosbc/ananke) [![License](https://poser.pugx.org/cmatosbc/ananke/license)](https://packagist.org/packages/cmatosbc/ananke)

A flexible PHP service container that supports conditional service instantiation. This package allows you to register services with multiple conditions that must be met before the service can be instantiated.

## Requirements

- PHP 8.0 or higher

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

## Condition Decorators

Ananke provides a powerful set of condition decorators that allow you to compose complex condition logic:

### Not Condition

Negate any condition:

```php
use Ananke\Conditions\{NotCondition, CallableCondition};

// Basic condition
$factory->registerCondition('is-maintenance', 
    new CallableCondition('is-maintenance', fn() => $maintenance->isActive()));

// Negate it
$factory->registerCondition('not-maintenance',
    new NotCondition($factory->getCondition('is-maintenance')));

// Use in service
$factory->register('api', APIService::class);
$factory->associateCondition('api', 'not-maintenance');
```

### Cached Condition

Cache expensive condition evaluations:

```php
use Ananke\Conditions\CachedCondition;

// Cache an expensive API check for 1 hour
$factory->registerCondition('api-status',
    new CachedCondition(
        new CallableCondition('api-check', fn() => $api->checkStatus()),
        3600 // Cache for 1 hour
    ));
```

### AND/OR Conditions

Combine multiple conditions with logical operators:

```php
use Ananke\Conditions\{AndCondition, OrCondition};

// Premium access: User must be premium OR have a trial subscription
$factory->registerCondition('can-access-premium',
    new OrCondition([
        new CallableCondition('is-premium', fn() => $user->isPremium()),
        new CallableCondition('has-trial', fn() => $user->hasTrial())
    ]));

// Database write: Need both connection AND proper permissions
$factory->registerCondition('can-write-db',
    new AndCondition([
        new CallableCondition('is-connected', fn() => $db->isConnected()),
        new CallableCondition('has-permissions', fn() => $user->canWrite())
    ]));
```

### XOR/NOR Conditions

For more complex logical operations, you can use XOR (exclusive OR) and NOR conditions:

```php
use Ananke\Conditions\{XorCondition, NorCondition};

// XOR: Feature must be enabled in EXACTLY one environment (dev XOR prod)
$factory->registerCondition('feature-enabled-single-env',
    new XorCondition([
        new CallableCondition('dev-enabled', fn() => $featureFlags->isEnabled('dev')),
        new CallableCondition('prod-enabled', fn() => $featureFlags->isEnabled('prod'))
    ]));

// NOR: Service is available only when NONE of the maintenance modes are active
$factory->registerCondition('all-systems-available',
    new NorCondition([
        new CallableCondition('db-maintenance', fn() => $maintenance->isDatabaseMaintenance()),
        new CallableCondition('api-maintenance', fn() => $maintenance->isApiMaintenance()),
        new CallableCondition('ui-maintenance', fn() => $maintenance->isUiMaintenance())
    ]));
```

### Complex Condition Compositions

Combine decorators for complex logic:

```php
// ((isPremium OR hasTrial) AND notMaintenance) AND (hasQuota OR isUnlimited)
$factory->registerCondition('can-use-service',
    new AndCondition([
        // Premium access check
        new OrCondition([
            new CallableCondition('premium', fn() => $user->isPremium()),
            new CallableCondition('trial', fn() => $user->hasTrial())
        ]),
        // Not in maintenance
        new NotCondition(
            new CallableCondition('maintenance', fn() => $maintenance->isActive())
        ),
        // Resource availability
        new OrCondition([
            new CallableCondition('has-quota', fn() => $user->hasQuota()),
            new CallableCondition('unlimited', fn() => $user->isUnlimited())
        ])
    ])
);

// Cache the entire complex condition
$factory->registerCondition('cached-access-check',
    new CachedCondition(
        $factory->getCondition('can-use-service'),
        300 // Cache for 5 minutes
    )
);
```

### Best Practices

1. **Caching**: Use `CachedCondition` for:
   - External API calls
   - Database queries
   - File system checks
   - Any expensive operations

2. **Composition**: Build complex conditions gradually:
   - Start with simple conditions
   - Combine them using AND/OR
   - Add negation where needed
   - Cache at appropriate levels

3. **Naming**: Use clear, descriptive names:
   - Negated: prefix with 'not-'
   - Cached: prefix with 'cached-'
   - Combined: use descriptive action names

4. **Testing**: Test complex conditions thoroughly:
   - Verify each sub-condition
   - Test boundary cases
   - Ensure proper short-circuit evaluation
   - Validate cache behavior

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