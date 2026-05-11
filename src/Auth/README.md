# Auth Folder Planning

The Auth folder acts as the orchestrator for the entire library. It provides the main entry point for developers.

## Key Components:
- `AuthManager.php`: A factory/manager class that creates and caches guard instances based on configuration.
- `GuardFactory.php`: Responsible for instantiating the correct driver.

## Usage Pattern:
```php
$auth = new AuthManager($config);
$user = $auth->guard('web')->user();
```
