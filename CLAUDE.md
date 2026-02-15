# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Cash** is a backend for small-community (e.g. family) shared finances management. Each community member has an income and contributes a configurable percentage to a common bank account used for shared expenses. The contribution percentage scales with income relative to the community's median income: higher earners contribute a higher percentage.

## Tech Stack

- **Language:** PHP
- **Framework:** [Flight PHP](https://flightphp.com/) v3 — a lightweight micro framework ([v3 docs](https://docs.flightphp.com/en/v3/learn))
- **Dependency management:** Composer

## PHP Environment

- Always use `php7.4` as the PHP executable when running Composer or any PHP CLI command.
- Example: `php7.4 /usr/local/bin/composer install` instead of `composer install`.

## Common Commands

```bash
# Install dependencies
php7.4 /usr/local/bin/composer install

# Start PHP built-in dev server (typical Flight setup)
php7.4 -S localhost:8000

# Run tests (once configured)
php7.4 /usr/local/bin/composer test
# or
php7.4 ./vendor/bin/phpunit
```

## Architecture

Flight PHP is a micro framework with route-based architecture. Key conventions:

- Routes are registered via `Flight::route()`
- Route parameters use `@` prefix: `/balance/@communityId/@memberId`
- Optional route parameters use parentheses: `/path/@required(/@optional1(/@optional2))` — unmatched params are `null`
- The framework entry point is typically a single `index.php` that bootstraps routes and middleware
- Flight uses `Flight::json()` for JSON responses (this is a REST API backend)

### Layered Architecture

The codebase follows a three-layer architecture for separation of concerns:

**Controllers** (`src/Controller/`)
- Handle HTTP concerns: request parsing, response formatting, status codes
- Validate authorization (JWT, ownership checks, community membership)
- Delegate business logic to services
- Delegate data access to repositories
- All controllers extend `Controller` abstract base class

**Services** (`src/Service/`)
- Contain business logic and domain rules
- Orchestrate operations across multiple repositories
- Example: `BalanceCalculator` computes balance from income and expense totals

**Repositories** (`src/Repository/`)
- Handle all database queries (PDO)
- One method per query — focused, testable, reusable
- Return raw arrays or primitive types
- All repositories extend `Repository` abstract base class
- Examples: `IncomeRepository`, `ExpenseRepository`, `MemberRepository`, `TransactionRepository`

**Example flow:**
```
Request → Controller (auth/validation) → Service (business logic) → Repository (data access) → Database
```

### Repository Pattern

All database queries are isolated in repository classes. Controllers and services never execute PDO queries directly.

**Benefits:**
- **Testability**: repositories are unit tested in isolation with in-memory SQLite
- **Reusability**: same query used by multiple controllers/services
- **Maintainability**: schema changes affect only repository methods
- **Readability**: `$repo->findById($id)` is clearer than inline SQL

**Convention:**
- One repository per table (or logical entity for complex queries like `TransactionRepository`)
- One method per query
- Methods named clearly: `findById()`, `create()`, `calculateTotalExpenses()`
- **Repositories accept DTOs**: `create()` and `update()` methods receive validated DTO instances

**DTO Usage in Repositories:**

Repositories receive validated DTOs instead of individual parameters:

```php
// Create method signature
public function create(int $memberId, IncomeDto $dto, int $contributionPercentage): int
{
    $stmt = $this->db->prepare('INSERT INTO income (memberId, date, reason, amount, contributionPercentage) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$memberId, $dto->date, $dto->reason, $dto->amount, $contributionPercentage]);
    return (int) $this->db->lastInsertId();
}

// Update method signature
public function update(int $id, IncomeUpdateDto $dto): bool
{
    // Build updates only from non-null DTO properties
    if ($dto->amount !== null) {
        $updates[] = "amount = ?";
        $params[] = $dto->amount;
    }
    // ... (repeat for other fields)
}
```

**Why DTOs in Repositories:**
- Data is already validated - no need to pass individual parameters
- Cleaner method signatures (2-3 params instead of 5+)
- Type-safe: DTOs guarantee validated, type-correct data
- Easier to extend: adding new fields only updates DTO, not all call sites

### DTO Pattern

Input validation for POST/PUT endpoints uses Data Transfer Objects (DTOs).

**Naming Convention**:
- **Uniform camelCase**: All property names, JSON keys, and database columns use camelCase
- **DTO properties**: camelCase (`contributionPercentage`, `ownerId`)
- **API input/output**: camelCase (`contributionPercentage`, `ownerId`)
- **Database columns**: camelCase (`contributionPercentage`, `ownerId`)
- **Response properties**: camelCase (`contributionPercentage`, `ownerId`)

This provides consistency across the entire stack - from database to API to client code.

**Location**: `src/Dto/`

**Naming**: `{Entity}Dto` (e.g., `IncomeDto`, `ExpenseDto`)

**Unified DTOs** — Single DTO per entity, used for both POST and PUT:
```php
class IncomeDto extends Dto
{
    public float $amount;                      // Type-hinted properties (all required)
    public string $reason;
    public string $date;
    public ?int $contributionPercentage;       // camelCase property names

    public static function createFromRequest(Request $request): self
    {
        // Extract data from snake_case input, validate, return DTO instance
        // All fields mandatory except contributionPercentage
        $dto->contributionPercentage = $validator->validateContributionPercentage(
            $data->contribution_percentage ?? null  // Input uses snake_case
        );
    }
}
```

**Example:**
```php
// Client sends: {"amount": 1500, "reason": "Salary", "date": "2025-02-15", "contributionPercentage": 75}
$dto = IncomeDto::createFromRequest($this->app->request());
// DTO has: $dto->contributionPercentage (camelCase property)
```

**Usage in Controllers**:

*Create (POST):*
```php
$dto = IncomeDto::createFromRequest($this->app->request());
// Use $dto->amount, $dto->reason, etc. (type-safe, validated, camelCase)
$contributionPercentage = $dto->contributionPercentage ?? $member['contribution_percentage'];
$incomeId = $this->incomeRepository->create($ownerId, $dto, $contributionPercentage);

// Return 201 Created with resource identifier
$this->json(new CreatedResourceResponse($incomeId, 'income'));
```

*Update (PUT):*
```php
$dto = IncomeDto::createFromRequest($this->app->request());
// PUT requires all fields (full replacement, not partial update)
$contributionPercentage = $dto->contributionPercentage ?? $income['contribution_percentage'];
$this->incomeRepository->update($id, $dto, $contributionPercentage);
```

**HTTP Methods**:
- **POST** `/income` — Create new income record (returns `{'id': X}` + Location header per RFC 9110)
- **PUT** `/income/@id` — Replace entire income record (all fields required)
- **GET** `/income/@id` — Retrieve income record
- **DELETE** `/income/@id` — Delete income record

**POST Response (RFC 9110)**:
- Status: `201 Created`
- Header: `Location: /income/{id}` (URI of created resource)
- Body: `{"id": 123}` (resource identifier only, not full entity)

**Responsibility Separation**:
- **DTOs**: Transport and validate data (no logic)
- **Controllers**: Handle defaults (e.g., contribution_percentage) and orchestrate

**Benefits**:
- Type-safe input handling with property type hints
- Single DTO per entity (simpler, less code duplication)
- Centralized validation logic in dedicated classes
- PUT semantics (full replacement) are clearer than PATCH (partial update)
- POST and PUT payloads are identical (except id in route)
- Clear separation of concerns (DTOs validate, controllers orchestrate)

**Validation**: DTOs delegate to existing `Validator` class methods, maintaining consistency with validation rules and error messages.

### Response Pattern

Output formatting uses Response classes for type safety and consistency.

**Location**: `src/Response/`

**Naming**: `{Entity}Response` (e.g., `IncomeResponse`, `ExpenseResponse`, `CreatedResourceResponse`)

**Base Class**: All response classes extend `AppResponse` (abstract base) which implements `JsonSerializable`

**Property Naming Convention**:
- **Response properties**: camelCase (`ownerId`, `createdAt`, `contributionPercentage`)
- **Database columns**: camelCase (`ownerId`, `createdAt`, `contributionPercentage`)
- **JSON output**: camelCase (via reflection-based serialization)

**Composable Responses** — Type-safe response objects with reflection-based serialization:

```php
// Abstract base for shared money flow fields
abstract class MoneyFlowResponse extends AppResponse
{
    public int $id;
    public int $ownerId;              // camelCase property
    public \DateTime $date;
    public string $reason;
    public string $amount;             // stored as string for JSON precision
    public \DateTime $createdAt;      // camelCase property
    public \DateTime $updatedAt;      // camelCase property

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->ownerId = (int) $data['ownerId'];        // DB uses camelCase
        $this->date = new \DateTime($data['date']);
        $this->reason = $data['reason'];
        $this->amount = (string) $data['amount'];
        $this->createdAt = new \DateTime($data['createdAt']);
        $this->updatedAt = new \DateTime($data['updatedAt']);
    }

    // No toArray() needed - AppResponse uses reflection to auto-serialize public properties
}

// Income-specific response extending shared base
class IncomeResponse extends MoneyFlowResponse
{
    public string $type = 'income';
    public ?string $contributionPercentage;  // camelCase property

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contributionPercentage = $data['contributionPercentage'] !== null
            ? (string) $data['contributionPercentage']
            : null;
    }
}

// Expense-specific response (inherits all from MoneyFlowResponse)
class ExpenseResponse extends MoneyFlowResponse
{
    public string $type = 'expense';
    // No contributionPercentage property - not applicable to expenses
}
```

**Automatic Serialization**:
- Public properties are automatically serialized by `AppResponse::toArray()` using reflection
- Property names become JSON keys directly (camelCase → camelCase)
- DateTime objects automatically formatted (Y-m-d or Y-m-d H:i:s)
- Nested Response objects recursively converted

**Usage in Controllers**:

```php
// GET /income/@id - Return single income record
$income = $this->incomeRepository->findById($id);
$this->json(new IncomeResponse($income));

// PUT /income/@id - Return updated income record
$this->incomeRepository->update($id, $dto, $contributionPercentage);
$updated = $this->incomeRepository->findById($id);
$this->json(new IncomeResponse($updated));

// GET /expense/@id - Return single expense record
$expense = $this->expenseRepository->findById($id);
$this->json(new ExpenseResponse($expense));
```

**Benefits**:
- Type-safe property access with camelCase naming (`$response->ownerId`)
- DateTime objects for proper date handling (not raw strings)
- Automatic serialization via reflection (no manual toArray() needed)
- Composable: responses can contain other Response objects
- Inheritance reduces duplication (MoneyFlowResponse shared by Income and Expense)
- Consistent naming: camelCase in PHP, camelCase in JSON output
- Self-documenting code (`IncomeResponse` vs. raw array)

**Response Classes**:
- `CreatedResourceResponse` - POST endpoints (201 + Location header + resource ID)
- `NoContentResponse` - DELETE endpoints (204 + empty body)
- `TokenPairResponse` - Auth endpoints (200 + access/refresh tokens)
- `BalanceResponse` - Balance endpoint (200 + memberId + balance as string for precision)
- `TransactionListResponse` - Transactions list (200 + transactions array + pagination)
- `Pagination` - Pagination metadata (composable, used by TransactionListResponse)
- `MoneyFlowResponse` - Abstract base for income/expense responses
- `IncomeResponse` - GET/PUT `/income/@id` endpoints (extends MoneyFlowResponse, adds type and contributionPercentage)
- `ExpenseResponse` - GET/PUT `/expense/@id` endpoints (extends MoneyFlowResponse, adds type field)

**Note**: Response classes have unit tests in `tests/Unit/Response/ResponseTest.php` and are also tested indirectly via Feature tests (testing actual HTTP responses).

## Development Principles

- **Test-Driven Development (TDD)**: write a failing test first, make it pass, then refactor. Every feature or bug fix starts with a test.
- **SOLID principles**:
  - **S**ingle Responsibility — each class does one thing
  - **O**pen/Closed — extend behavior without modifying existing code
  - **L**iskov Substitution — subtypes must be substitutable for their base types
  - **I**nterface Segregation — prefer small, focused interfaces over large ones
  - **D**ependency Inversion — depend on abstractions, not concretions; inject dependencies
- Keep classes small and methods short. Favor composition over inheritance.
- Name things clearly — code should read like prose, not puzzles.
- Prefer to throw exceptions instead of returning after printing a JSON.

## Documentation

Keep documentation updated.
- **Routes**: when adding or modifying a route behavior update README.md and openapi.yaml
- **Commands**: when adding or modifying a command behavior update README.md 
- **Project structure**: when adding or modifying project structure behavior update README.md

# Domain Concepts

- **Community**: a group of people (e.g. a family) sharing expenses
- **Member**: a person in the community with an income
- **Income**: the earnings of a member
- **Median income**: the statistical median of all members' incomes, used as the reference point for contribution rates
- **Contribution percentage**: the share of income a member puts into the common account — scales progressively based on income relative to the median
- **Common account**: the shared bank account funded by contributions, used for community expenses

## Database

- **Engine:** SQLite
- **DB file:** `database/cash.db` (git-ignored)
- **Schema:** `database/schema.sql` — contains DDL, indexes, triggers, and seed data
- **Initialize:** `php database/init_db.php` — creates the DB from the schema. Refuses to run if the DB file already exists; delete it first to reinitialize.
- **Tables:** `community`, `member`, `income`, `expense`
- **Seed data:** one community ("Family"), two members (first/85%, second/75%)

## Learnings & Suggestions

When you notice a meaningful pattern, a recurring mistake, or a useful tip for this project, update `.claude/tips.md`. Do this only when genuinely relevant — not on every prompt. Examples of when to write:

- A debugging approach that worked (or didn't) for this codebase
- A project-specific convention discovered while reading existing code
- A constraint or gotcha worth remembering for future sessions

When you discover something useful about the Flight PHP framework — a gotcha, a workaround, an undocumented behavior, or a best practice — update the `/flightphp` skill (`.claude/skills/flightphp/SKILL.md`) so the knowledge is available in future sessions.
