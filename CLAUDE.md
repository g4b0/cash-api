# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Cash** is a backend for small-community (e.g. family) shared finances management. Each community member has an income and contributes a configurable percentage to a common bank account used for shared expenses. The contribution percentage scales with income relative to the community's median income: higher earners contribute a higher percentage.

## Tech Stack

- **Language:** PHP
- **Framework:** [Flight PHP](https://flightphp.com/) v3 — a lightweight micro framework
- **Dependency management:** Composer

## Common Commands

```bash
# Install dependencies
composer install

# Start PHP built-in dev server (typical Flight setup)
php -S localhost:8000

# Run tests (once configured)
composer test
# or
./vendor/bin/phpunit
```

## Architecture

Flight PHP is a micro framework with route-based architecture. Key conventions:

- Routes are registered via `Flight::route()`
- The framework entry point is typically a single `index.php` that bootstraps routes and middleware
- Flight uses `Flight::json()` for JSON responses (this is a REST API backend)

## Domain Concepts

- **Community**: a group of people (e.g. a family) sharing expenses
- **Member**: a person in the community with an income
- **Income**: the earnings of a member
- **Median income**: the statistical median of all members' incomes, used as the reference point for contribution rates
- **Contribution percentage**: the share of income a member puts into the common account — scales progressively based on income relative to the median
- **Common account**: the shared bank account funded by contributions, used for community expenses
