# Middleware Folder Planning

Contains logic for filtering HTTP requests. These will be designed to be framework-agnostic but easily adaptable to CI3 or PSR-15.

## Planned Middlewares:
- `Authenticate.php`: Redirects or returns error if not authenticated.
- `Authorize.php`: Checks if the user has specific permissions.
- `Guest.php`: Ensures only unauthenticated users can access a route (e.g., login page).
