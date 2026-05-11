# DarkAuth

[![Latest Stable Version](https://img.shields.io/packagist/v/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)
[![License](https://img.shields.io/packagist/l/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)

**DarkAuth** is a flexible, production-grade authentication library specifically designed to bring modern authentication patterns to legacy **CodeIgniter 3** applications and PHP 7.4+ environments.

## Features

- **Multi-Driver Architecture**: Switch between `Session` (Web) and `JWT` (API) seamlessly.
- **Modern Security**: Argon2id hashing with bcrypt fallback.
- **CI3 Ready**: Plug-and-play adapter for CodeIgniter 3.1.13+.
- **Stateless & Stateful**: Full support for both traditional session-based and modern token-based auth.
- **SOLID Principles**: Clean, testable, and extensible code following PSR-4 standards.
- **Session Fixation Protection**: Automatically regenerates session IDs on login.

## Installation

Install via Composer:

```bash
composer require darkauth/darkauth
```

## Quick Start (CodeIgniter 3)

1. Load the library in your controller:
```php
$this->load->library(\Darkauth\Support\CI3Auth::class, null, 'darkauth');
```

2. Basic usage:
```php
// Login a user
$user = new \Darkauth\Models\GenericUser(['id' => 1, 'username' => 'admin']);
$this->darkauth->login($user);

// Check if authenticated
if ($this->darkauth->check()) {
    echo "Welcome, " . $this->darkauth->user()->username;
}

// Issue JWT for API
$token = $this->darkauth->guard('api')->issueToken($this->darkauth->user());
```

## Documentation

For detailed planning and roadmap of each component, see the `README.md` files in each directory:
- [Core Planning](src/Core/README.md)
- [Drivers Planning](src/Drivers/README.md)
- [Auth Manager Planning](src/Auth/README.md)

## Requirements

- PHP 7.4.33 or higher
- CodeIgniter 3.1.13 (optional, for CI3 integration)
- `firebase/php-jwt` for JWT support

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
