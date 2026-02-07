# Business Logic Rules

Core domain rules for the Cash application. All features and tests must conform to these rules.

## Authentication & Authorization

- **JWT-based** login and logout.
- **All endpoints require authentication** — unauthenticated requests must be rejected.
- **Write access is self-only**: users can only create or modify their own Income/Expense records. The owner is always the authenticated user.
- **Read access is open within the community**: any authenticated user can view any member's dashboard (transaction history + balance). This is read-only.
- If a requested member does not exist, respond with **403 Forbidden**.

## Data Models

Income and Expense are **separate tables** (no shared "type" column).

### Income

| Field                   | Type          | Notes                                        |
|-------------------------|---------------|----------------------------------------------|
| date                    | date          | When the income was recorded                 |
| reason                  | string(255)   | Description / reason                         |
| amount                  | decimal       | Always **> 0**                               |
| contribution_percentage | integer       | Snapshot of the owner's rate at write time    |
| owner                   | FK to Member  | The member who earned this income            |

- `contribution_percentage` is stamped onto the record at creation time using the owner's currently configured rate. It is **not recalculated** later.

### Expense

| Field  | Type          | Notes                              |
|--------|---------------|------------------------------------|
| date   | date          | When the expense was recorded      |
| reason | string(255)   | Description / reason               |
| amount | decimal       | Always **> 0**                     |
| owner  | FK to Member  | The member who incurred the expense|

### Member

Each member belongs to a community and has:
- A configurable **contribution percentage** (default: **75%**).
- An **income**, used to determine their contribution rate relative to the community median.

## Contribution Percentage

The contribution percentage is the share of a member's income that goes to the common account.

- **Default**: 75%.
- **Progressive**: higher income relative to the community median results in a higher percentage.
- **Configurable per member**: the rate is stored on the Member and can be adjusted.
- **Stamped at write time**: when an Income record is created, the owner's current percentage is copied onto the record. This preserves historical accuracy — later rate changes do not affect past records.

## Balance Calculation

The balance is computed at read time by iterating over all of a member's Income and Expense records:

```
total = 0

for each Income record:
    total += amount * (contribution_percentage / 100)

for each Expense record:
    total -= amount
```

### Sign convention

- **Positive total** = member is in **debit** (owes the community)
- **Negative total** = member is in **credit** (community owes them)

### Key rules

- Only the **contributed portion** of income counts (scaled by percentage).
- The **full amount** of expenses is subtracted (no percentage applied).
- Amounts are always stored as **positive values** in both tables. The sign is determined by the record type (Income = add, Expense = subtract).

## Validation

- `amount` must be **> 0** on both Income and Expense. Reject zero or negative values.
- `reason` is required.
- `date` is required.
- `owner` is always the authenticated user (never accept a user-supplied owner).

## API Behavior

- **Recording income or expense**: a database write followed by an acknowledgement response. No side effects, no recalculations triggered.
- **Dashboard**: reads the member's transaction history, computes the credit/debit balance, and returns both.
