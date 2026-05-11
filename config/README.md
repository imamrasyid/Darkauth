# Config Folder Planning

This folder contains the default configuration files for the library.

## Planned Files:
- `auth.php`: Main configuration (default guard, providers, etc.).
- `jwt.php`: JWT specific settings (secret key, TTL, algorithm).

## Implementation:
Users should be able to override these defaults by passing an array to the `AuthManager`.
