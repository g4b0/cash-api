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
- Route parameters use `@` prefix: `/balance/@community_id/@member_id`
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

### DTO Pattern

Input validation for POST/PUT/PATCH endpoints uses Data Transfer Objects (DTOs).

**Location**: `src/Dto/`

**Naming**: `{Entity}{Operation}Dto` (e.g., `IncomeCreateDto`, `ExpenseUpdateDto`)

**Create DTOs (POST)** — Required fields:
```php
class IncomeCreateDto extends Dto
{
    public float $amount;           // Type-hinted properties
    public string $reason;
    public string $date;
    public ?int $contribution_percentage;

    public static function createFromRequest(Request $request): self
    {
        // Extract data, validate, return DTO instance
    }
}
```

**Update DTOs (PATCH)** — All fields optional (nullable):
```php
class IncomeUpdateDto extends Dto
{
    public ?float $amount = null;
    public ?string $reason = null;
    public ?string $date = null;
    public ?int $contribution_percentage = null;

    public static function createFromRequest(Request $request): self
    {
        // Validate only provided fields, leave others null
    }
}
```

**Usage in Controllers**:

*Create (POST):*
```php
$dto = IncomeCreateDto::createFromRequest($this->app->request());
// Use $dto->amount, $dto->reason, etc. (type-safe, validated)
```

*Update (PATCH):*
```php
$dto = IncomeUpdateDto::createFromRequest($this->app->request());

// Controller builds updates array from non-null properties
$updates = [];
if ($dto->amount !== null) {
    $updates['amount'] = $dto->amount;
}
// ... repeat for other fields
```

**Responsibility Separation**:
- **DTOs**: Transport and validate data (no logic)
- **Controllers**: Handle null checks and build updates array

**Benefits**:
- Type-safe input handling with property type hints
- Centralized validation logic in dedicated classes
- Reduced controller boilerplate
- POST payloads align with GET response structure
- Clear separation of concerns (DTOs validate, controllers orchestrate)

**Validation**: DTOs delegate to existing `Validator` class methods, maintaining consistency with validation rules and error messages.

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
