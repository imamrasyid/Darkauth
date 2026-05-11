# Drivers Folder Planning

This folder contains concrete implementations of the `GuardInterface`. Drivers define how the library interacts with specific authentication mechanisms.

## Planned Drivers:
- `SessionGuard.php`: Handles standard web authentication using PHP sessions (ideal for CI3 apps).
- `JWTGuard.php`: Handles stateless API authentication using JSON Web Tokens.
- `CookieGuard.php`: (Optional) Persistent login via "Remember Me" cookies.

## Responsibilities:
- Managing authentication state.
- Retrieving users based on credentials or tokens.
- Handling login/logout logic specific to the driver.
