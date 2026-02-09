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
- The framework entry point is typically a single `index.php` that bootstraps routes and middleware
- Flight uses `Flight::json()` for JSON responses (this is a REST API backend)

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

## Domain Concepts

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
