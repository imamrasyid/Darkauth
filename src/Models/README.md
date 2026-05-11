# Models Folder Planning

Contains data objects used throughout the library to represent entities.

## Planned Models:
- `GenericUser.php`: A simple, ready-to-use User model that implements `UserInterface`.
- `SessionData.php`: Value object to represent session information.

## Goal:
Keep these models decoupled from any specific database ORM, allowing them to be used in plain PHP or within frameworks like CI3.
