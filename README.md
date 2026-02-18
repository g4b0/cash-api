# Cash - Family Shared Finances API

A backend REST API for managing shared finances in small communities (e.g., families). Each member contributes a percentage of their income to a common account based on their earnings relative to the community's median income — higher earners contribute more.

**🤖 A great part of this codebase was written by AI. Few lines of code were written by humans.**

[![Tests](https://github.com/g4b0/cash-api/actions/workflows/tests.yml/badge.svg)](https://github.com/g4b0/cash-api/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/g4b0/cash-api/branch/master/graph/badge.svg)](https://codecov.io/gh/g4b0/cash-api)

## Features

- **JWT Authentication**: Secure login with access and refresh tokens
- **Income & Expense Management**: Full CRUD operations for income and expense records
- **Progressive Contribution**: Contribution percentage scales with income relative to median
- **Balance**: View member balance (contributions minus expenses)
- **Transactions List**: Paginated list of merged income/expense transactions, sorted by date
- **Input Validation**: Centralized validator with comprehensive error handling
- **REST API**: JSON responses, built with Flight PHP v3
- **CLI Commands**: Manage communities and members from the terminal
- **OpenAPI Specification**: Complete API documentation (importable to ApiDog, Postman, etc.)
- **Full Test Coverage**: TDD approach with PHPUnit (204 tests, 472 assertions)

## Tech Stack

- **PHP 7.4–8.5**
- **Flight PHP v3** (micro-framework)
- **SQLite** (database)
- **firebase/php-jwt** (JWT authentication)
- **PHPUnit 9.6** (testing)

## Installation

```bash
# Install dependencies
php /usr/local/bin/composer install
```

## Database Initialization

```bash
# Initialize the database (creates database/cash.db from schema)
php database/init_db.php
```

**Note:** The init script refuses to run if `database/cash.db` already exists. Delete it first to reinitialize:

```bash
rm database/cash.db && php database/init_db.php
```

### Database Schema

- **community**: Groups of people sharing expenses
- **member**: People in a community (with username/password)
- **income**: Member earnings with contribution percentage
- **expense**: Costs paid from the common account

## Running the Server

```bash
# Start the PHP built-in development server
php -S localhost:8000
```

## API Endpoints

**📄 Complete API documentation**: See [`openapi.yaml`](./openapi.yaml) for the full OpenAPI 3.0 specification (importable to ApiDog, Postman, Swagger, etc.)

### Authentication (Public Routes)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Authenticate and get JWT tokens |
| POST | `/refresh` | Refresh JWT tokens |

**Example: Login**
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser", "password": "secret"}'
```

Response:
```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Balance (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| GET | `/balance/{communityId}/{memberId}` | Get member's balance | Same community |

**Example: Get Balance**
```bash
curl http://localhost:8000/balance/1/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Authorization**: User must belong to the same community as the requested member.

**Balance calculation**: `SUM(income × contributionPercentage ÷ 100) - SUM(expenses)`

### Transactions List (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| GET | `/transactions/{communityId}/{memberId}[/{num}[/{page}]]` | Get paginated list of member's transactions | Same community |

**Example: Get Transactions (Default)**
```bash
curl http://localhost:8000/transactions/1/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Example: Custom Pagination**
```bash
# 10 items per page, page 2
curl http://localhost:8000/transactions/1/1/10/2 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Parameters:**
- `num` (optional): Items per page (1-100), defaults to 25
- `page` (optional): Page number (≥1), defaults to 1

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "income",
      "date": "2025-02-14",
      "reason": "Salary",
      "amount": "1000.50",
      "contributionPercentage": 75,
      "createdAt": "2025-02-14 10:00:00",
      "updatedAt": "2025-02-14 10:00:00"
    },
    {
      "id": 2,
      "type": "expense",
      "date": "2025-02-13",
      "reason": "Groceries",
      "amount": "500.00",
      "contributionPercentage": null,
      "createdAt": "2025-02-13 15:30:00",
      "updatedAt": "2025-02-13 15:30:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 67,
    "perPage": 25
  }
}
```

**Authorization**: User must belong to the same community as the requested member.

**Sorting**: Transactions are sorted by date descending (newest first).

### Income Management (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| POST | `/income` | Create income record | Owner only |
| GET | `/income/{id}` | Get income record | Same community |
| PATCH | `/income/{id}` | Update income record | Owner only |
| DELETE | `/income/{id}` | Delete income record | Owner only |

**Example: Create Income**
```bash
curl -X POST http://localhost:8000/income \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000.50,
    "reason": "Monthly salary",
    "date": "2025-02-14",
    "contributionPercentage": 80
  }'
```

**Fields:**
- `amount` (required): Must be > 0
- `reason` (required): Description of income
- `date` (optional): Defaults to today (format: YYYY-MM-DD)
- `contributionPercentage` (optional): Defaults to member's current percentage (0-100)

### Expense Management (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| POST | `/expense` | Create expense record | Owner only |
| GET | `/expense/{id}` | Get expense record | Same community |
| PATCH | `/expense/{id}` | Update expense record | Owner only |
| DELETE | `/expense/{id}` | Delete expense record | Owner only |

**Example: Create Expense**
```bash
curl -X POST http://localhost:8000/expense \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500.75,
    "reason": "Groceries",
    "date": "2025-02-14"
  }'
```

**Fields:**
- `amount` (required): Must be > 0
- `reason` (required): Description of expense
- `date` (optional): Defaults to today (format: YYYY-MM-DD)

**Example: Update Expense (Partial)**
```bash
curl -X PATCH http://localhost:8000/expense/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 600}'
```

All fields are optional in PATCH requests - only provided fields will be updated.

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
  --communityId=1 \
  --name="John Doe" \
  --username=johndoe \
  --password=secret123 \
  --contributionPercentage=85

# Update a member (any combination of fields)
./bin/member update --id=1 --name="Jane Doe"
./bin/member update --id=1 --contributionPercentage=90
./bin/member update --id=1 --password=newpassword
./bin/member update --id=1 --username=newusername --name="New Name"

# Delete a member
./bin/member delete --id=1
```

**Note:** `contributionPercentage` defaults to 75 if not specified.

## Running Tests

```bash
# Run all tests (may show "risky test" warnings - these are harmless)
php ./vendor/bin/phpunit

# Run with clean output (no risky warnings)
php ./vendor/bin/phpunit --dont-report-useless-tests

# Run with verbose output
php ./vendor/bin/phpunit --testdox

# Run specific test suite
php ./vendor/bin/phpunit tests/Feature/
php ./vendor/bin/phpunit tests/Unit/
```

**Note on "risky test" warnings**: Flight PHP's output buffering can cause PHPUnit to report tests as "risky". This is expected behavior and doesn't affect test correctness or application functionality. The warnings are suppressed with `--dont-report-useless-tests` or by the `phpunit.xml` configuration (tests won't fail, warnings just display).

## Code Coverage

**Prerequisites**: Install Xdebug for code coverage:

```bash
# Install Xdebug for PHP 7.4
sudo apt-get install php-xdebug

# Verify installation
php -m | grep xdebug
```

Generate coverage reports locally:

```bash
# Generate HTML and Clover reports (XDEBUG_MODE=coverage is set automatically)
php /usr/local/bin/composer test:coverage

# View HTML coverage report in browser
open tests/coverage/html/index.html

# Quick text summary in terminal
php /usr/local/bin/composer test:coverage:text
```

**Alternative**: Run PHPUnit directly with Xdebug mode:

```bash
# Set XDEBUG_MODE and run PHPUnit manually
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-html tests/coverage/html --coverage-clover tests/coverage/clover.xml
```

**Coverage Reports:**
- **HTML Report**: `tests/coverage/html/index.html` - Interactive, visual coverage browser
- **Clover XML**: `tests/coverage/clover.xml` - Machine-readable format for CI/CD
- **Text Output**: Terminal summary of coverage percentages

**GitHub Integration**: When published, coverage is automatically tracked via Codecov.io. Coverage badges and PR comments show coverage trends (Xdebug installed automatically in CI).

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
│   ├── Exception/         # Custom exceptions (AppException)
│   ├── Http/
│   │   ├── Controller/    # HTTP controllers (Auth, Income, Expense, Transactions)
│   │   ├── Dto/           # Data Transfer Objects for input validation
│   │   ├── Middleware/    # JWT auth & exception handling
│   │   └── Response/      # Type-safe response objects
│   │       └── Crud/      # Generic CRUD responses (Created, NoContent)
│   ├── Repository/        # Database queries (Income, Expense, Member, Transaction)
│   ├── Service/           # Business logic (JWT, balance calc)
│   ├── Validation/        # Input validators (shared Validator class)
│   └── routes.php         # Route definitions
├── tests/
│   ├── Feature/           # Feature/integration tests (HTTP routes)
│   ├── Unit/              # Unit tests (validators, exceptions, commands)
│   └── Support/           # Test helpers (DatabaseSeeder)
├── index.php              # Application entry point
├── openapi.yaml           # OpenAPI 3.0 specification (API documentation)
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

### Layered Architecture

The codebase follows a clean three-layer separation of concerns:

**Controllers** → **Services** → **Repositories** → **Database**

- **Controllers** (`src/Http/Controller/`): Handle HTTP concerns (routing, validation, JSON responses)
- **Services** (`src/Service/`): Contain business logic (e.g., `BalanceCalculator`)
- **Repositories** (`src/Repository/`): Execute all database queries (one method per query)

**Repository Pattern benefits:**
- All PDO queries isolated in testable, reusable methods
- Controllers/services never write SQL directly
- Each repository method is unit tested in isolation
- Examples: `IncomeRepository::calculateTotalContributions()`, `ExpenseRepository::findById()`

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
- **Contributions** = `SUM(income_amount × contributionPercentage ÷ 100)`
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

**Generated with the great help of Claude Opus 4.6 and Claude Sonnet 4.5** 🤖✨
