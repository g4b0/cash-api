# Cash - Family Shared Finances API

A backend REST API for managing shared finances in small communities (e.g., families). Each member contributes a percentage of their income to a common account based on their earnings relative to the community's median income — higher earners contribute more.

**🤖 This entire codebase was written by AI. Zero lines of code were written by humans.**

## Features

- **JWT Authentication**: Secure login with access and refresh tokens
- **Progressive Contribution**: Contribution percentage scales with income relative to median
- **Member Dashboard**: View balance (contributions minus expenses)
- **REST API**: JSON responses, built with Flight PHP v3
- **CLI Commands**: Manage communities and members from the terminal
- **Full Test Coverage**: TDD approach with PHPUnit (33 tests, 82 assertions)

## Tech Stack

- **PHP 7.4**
- **Flight PHP v3** (micro-framework)
- **SQLite** (database)
- **firebase/php-jwt** (JWT authentication)
- **PHPUnit 9.6** (testing)

## Installation

```bash
# Install dependencies
php7.4 /usr/local/bin/composer install
```

## Database Initialization

```bash
# Initialize the database (creates database/cash.db from schema)
php7.4 database/init_db.php
```

**Note:** The init script refuses to run if `database/cash.db` already exists. Delete it first to reinitialize:

```bash
rm database/cash.db && php7.4 database/init_db.php
```

### Database Schema

- **community**: Groups of people sharing expenses
- **member**: People in a community (with username/password)
- **income**: Member earnings with contribution percentage
- **expense**: Costs paid from the common account

## Running the Server

```bash
# Start the PHP built-in development server
php7.4 -S localhost:8000
```

## API Endpoints

### Authentication

**POST /login**
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser", "password": "secret"}'
```

Response:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**POST /refresh**
```bash
curl -X POST http://localhost:8000/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."}'
```

### Dashboard

**GET /:community_id/:member_id**
```bash
curl http://localhost:8000/1/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

Response:
```json
{
  "balance": 625.0
}
```

**Balance calculation**: `SUM(income * contribution_percentage / 100) - SUM(expenses)`

## CLI Commands

### Community Management

```bash
# Add a new community
./bin/community add --name="My Family"

# Update a community
./bin/community update --id=1 --name="Updated Name"

# Delete a community
./bin/community delete --id=1
```

### Member Management

```bash
# Add a new member
./bin/member add \
  --community_id=1 \
  --name="John Doe" \
  --username=johndoe \
  --password=secret123 \
  --contribution_percentage=85

# Update a member (any combination of fields)
./bin/member update --id=1 --name="Jane Doe"
./bin/member update --id=1 --contribution_percentage=90
./bin/member update --id=1 --username=newusername --name="New Name"

# Delete a member
./bin/member delete --id=1
```

**Note:** `contribution_percentage` defaults to 75 if not specified.

## Running Tests

```bash
# Run all tests
php7.4 ./vendor/bin/phpunit

# Run with verbose output
php7.4 ./vendor/bin/phpunit --testdox

# Run specific test suite
php7.4 ./vendor/bin/phpunit tests/Feature/
php7.4 ./vendor/bin/phpunit tests/Unit/
```

## Project Structure

```
cash/
├── bin/
│   ├── community          # CLI for community management
│   └── member             # CLI for member management
├── database/
│   ├── schema.sql         # Database schema with seed data
│   ├── init_db.php        # Database initialization script
│   └── cash.db            # SQLite database (created after init)
├── src/
│   ├── Command/           # CLI command classes
│   ├── Controller/        # HTTP controllers
│   ├── Exception/         # Custom exceptions
│   ├── Middleware/        # JWT auth & exception handling
│   ├── Service/           # Business logic (JWT, balance calc)
│   └── routes.php         # Route definitions
├── tests/
│   ├── Feature/           # Feature/integration tests
│   ├── Unit/              # Unit tests
│   └── Support/           # Test helpers (DatabaseSeeder)
├── index.php              # Application entry point
└── composer.json          # Dependencies
```

## Architecture Highlights

### Exception-Based Error Handling

All errors are thrown as `AppException` instances with automatic HTTP status code mapping:

```php
if (empty($username)) {
    throw AppException::USERNAME_REQUIRED();  // Automatically returns 400
}

if (!password_verify($password, $hash)) {
    throw AppException::INVALID_CREDENTIALS();  // Automatically returns 401
}
```

### JWT Middleware via `map('start')`

Flight PHP v3's `addMiddleware()` hardcodes 403 responses. The auth middleware uses `map('start')` to override the start event, allowing custom 401 responses:

```php
JwtAuthMiddleware::register($app);  // Protects all routes except /login and /refresh
```

### TDD Approach

Every feature was developed test-first:
1. Write failing test (red)
2. Implement minimum code to pass (green)
3. Refactor

## Business Logic

### Progressive Contribution System

Members contribute to the common account based on their income relative to the community's median:
- Higher earners → higher contribution percentage
- Lower earners → lower contribution percentage
- Contribution percentage is stored per-income record for historical accuracy

### Balance Calculation

**Formula**: `Contributions - Expenses`

Where:
- **Contributions** = `SUM(income_amount × contribution_percentage ÷ 100)`
- **Expenses** = `SUM(expense_amount)`

Positive balance = money owed to the member (debit)
Negative balance = money the member owes (credit)

## Development

### Adding New Exceptions

1. Add constant to `AppException`:
```php
public const MY_ERROR = 'Error message';
```

2. Add to status code mapping in `getStatusCode()`:
```php
case 'MY_ERROR':
    return 400;  // or 401, 404, etc.
```

3. Use it:
```php
throw AppException::MY_ERROR();
```

### Adding New Routes

1. Create controller in `src/Controller/`
2. Add route in `src/routes.php`:
```php
$app->route('GET /path', [MyController::class, 'method']);
```

3. Write tests first (TDD)!

## License

This project was created as an AI demonstration. Use at your own discretion.

---

**Generated entirely by Claude Opus 4.6 and Claude Sonnet 4.5** 🤖✨
