# Core Folder Planning

This folder contains the foundation of the Darkauth library. It defines the contracts (interfaces) and abstract classes that ensure consistency across different implementations.

## Key Components:
- `UserInterface.php`: Defines the methods a User model must implement (e.g., `getId()`, `getAuthIdentifier()`).
- `GuardInterface.php`: Defines the contract for authentication guards (e.g., `check()`, `user()`, `login()`, `logout()`).
- `StorageInterface.php`: Defines how data is stored and retrieved (Database, Session, etc.).
- `AuthenticatableTrait.php`: A trait to provide default implementations for `UserInterface`.

## Principles:
- **Strict Typing**: Use PHP 7.4+ type hinting for all methods.
- **Dependency Inversion**: High-level modules should depend on these interfaces, not on concrete implementations.
