# Cash - Family Shared Finances API

A backend REST API for managing shared finances in small communities (e.g., families). Each member contributes a percentage of their income to a common account based on their earnings relative to the community's median income — higher earners contribute more.

**🤖 This entire codebase was written by AI. Zero lines of code were written by humans.**

## Features

- **JWT Authentication**: Secure login with access and refresh tokens
- **Income & Expense Management**: Full CRUD operations for income and expense records
- **Progressive Contribution**: Contribution percentage scales with income relative to median
- **Member Dashboard**: View balance (contributions minus expenses)
- **Transactions List**: Paginated list of merged income/expense transactions, sorted by date
- **Input Validation**: Centralized validator with comprehensive error handling
- **REST API**: JSON responses, built with Flight PHP v3
- **CLI Commands**: Manage communities and members from the terminal
- **OpenAPI Specification**: Complete API documentation (importable to ApiDog, Postman, etc.)
- **Full Test Coverage**: TDD approach with PHPUnit (171 tests, 393 assertions)

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
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Dashboard (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| GET | `/balance/{community_id}/{member_id}` | Get member's balance | Same community |

**Example: Get Balance**
```bash
curl http://localhost:8000/balance/1/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Authorization**: User must belong to the same community as the requested member.

**Balance calculation**: `SUM(income × contribution_percentage ÷ 100) - SUM(expenses)`

### Transactions List (Authenticated)

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| GET | `/transactions/{community_id}/{member_id}[/{num}[/{page}]]` | Get paginated list of member's transactions | Same community |

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
      "contribution_percentage": 75,
      "created_at": "2025-02-14 10:00:00",
      "updated_at": "2025-02-14 10:00:00"
    },
    {
      "id": 2,
      "type": "expense",
      "date": "2025-02-13",
      "reason": "Groceries",
      "amount": "500.00",
      "contribution_percentage": null,
      "created_at": "2025-02-13 15:30:00",
      "updated_at": "2025-02-13 15:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 67,
    "per_page": 25
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
    "contribution_percentage": 80
  }'
```

**Fields:**
- `amount` (required): Must be > 0
- `reason` (required): Description of income
- `date` (optional): Defaults to today (format: YYYY-MM-DD)
- `contribution_percentage` (optional): Defaults to member's current percentage (0-100)

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
# Run all tests (may show "risky test" warnings - these are harmless)
php7.4 ./vendor/bin/phpunit

# Run with clean output (no risky warnings)
php7.4 ./vendor/bin/phpunit --dont-report-useless-tests

# Run with verbose output
php7.4 ./vendor/bin/phpunit --testdox

# Run specific test suite
php7.4 ./vendor/bin/phpunit tests/Feature/
php7.4 ./vendor/bin/phpunit tests/Unit/
```

**Note on "risky test" warnings**: Flight PHP's output buffering can cause PHPUnit to report tests as "risky". This is expected behavior and doesn't affect test correctness or application functionality. The warnings are suppressed with `--dont-report-useless-tests` or by the `phpunit.xml` configuration (tests won't fail, warnings just display).

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
│   ├── Controller/        # HTTP controllers (Auth, Dashboard, Income, Expense, Transactions)
│   ├── Dto/               # Data Transfer Objects for input validation
│   ├── Exception/         # Custom exceptions (AppException)
│   ├── Middleware/        # JWT auth & exception handling
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

- **Controllers** (`src/Controller/`): Handle HTTP concerns (routing, validation, JSON responses)
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
