-- Cash database schema for SQLite
-- Separate tables for income and expense, with community and member support.

PRAGMA foreign_keys = ON;

-- Community: a group of people sharing expenses
CREATE TABLE community
(
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT     NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Member: a person in a community with a contribution percentage
CREATE TABLE member
(
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    community_id            INTEGER  NOT NULL,
    name                    TEXT     NOT NULL,
    username                TEXT     NOT NULL UNIQUE,
    password                TEXT     NOT NULL,
    contribution_percentage INTEGER  NOT NULL DEFAULT 75,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES community (id)
);

-- Income: earnings recorded by a member
CREATE TABLE income
(
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id                INTEGER       NOT NULL,
    date                    DATE          NOT NULL,
    reason                  TEXT          NOT NULL,
    amount                  DECIMAL(10,2) NOT NULL,
    contribution_percentage INTEGER       NOT NULL,
    created_at              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES member (id)
);

-- Expense: costs incurred by a member
CREATE TABLE expense
(
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id   INTEGER       NOT NULL,
    date       DATE          NOT NULL,
    reason     TEXT          NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES member (id)
);

-- Indexes
CREATE INDEX idx_member_community_id ON member (community_id);
CREATE INDEX idx_income_owner_id ON income (owner_id);
CREATE INDEX idx_income_date ON income (date);
CREATE INDEX idx_expense_owner_id ON expense (owner_id);
CREATE INDEX idx_expense_date ON expense (date);

-- Triggers: auto-update updated_at on row modification
CREATE TRIGGER update_community_timestamp
    AFTER UPDATE ON community
BEGIN
    UPDATE community SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_member_timestamp
    AFTER UPDATE ON member
BEGIN
    UPDATE member SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_income_timestamp
    AFTER UPDATE ON income
BEGIN
    UPDATE income SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_expense_timestamp
    AFTER UPDATE ON expense
BEGIN
    UPDATE expense SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
