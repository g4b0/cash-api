-- Cash database schema for SQLite
-- Separate tables for income and expense, with community and member support.
-- All column names use camelCase for consistency with application code.

PRAGMA foreign_keys = ON;

-- Community: a group of people sharing expenses
CREATE TABLE community
(
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    name      TEXT     NOT NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Member: a person in a community with a contribution percentage
CREATE TABLE member
(
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    communityId            INTEGER  NOT NULL,
    name                   TEXT     NOT NULL,
    username               TEXT     NOT NULL UNIQUE,
    password               TEXT     NOT NULL,
    contributionPercentage INTEGER  NOT NULL DEFAULT 75,
    createdAt              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (communityId) REFERENCES community (id)
);

-- Income: earnings recorded by a member
CREATE TABLE income
(
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    ownerId                INTEGER       NOT NULL,
    date                   DATE          NOT NULL,
    reason                 TEXT          NOT NULL,
    amount                 DECIMAL(10,2) NOT NULL,
    contributionPercentage INTEGER       NOT NULL,
    createdAt              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ownerId) REFERENCES member (id)
);

-- Expense: costs incurred by a member
CREATE TABLE expense
(
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    ownerId   INTEGER       NOT NULL,
    date      DATE          NOT NULL,
    reason    TEXT          NOT NULL,
    amount    DECIMAL(10,2) NOT NULL,
    createdAt DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ownerId) REFERENCES member (id)
);

-- Indexes
CREATE INDEX idx_member_communityId ON member (communityId);
CREATE INDEX idx_income_ownerId ON income (ownerId);
CREATE INDEX idx_income_date ON income (date);
CREATE INDEX idx_expense_ownerId ON expense (ownerId);
CREATE INDEX idx_expense_date ON expense (date);

-- Triggers: auto-update updatedAt on row modification
CREATE TRIGGER update_community_timestamp
    AFTER UPDATE ON community
BEGIN
    UPDATE community SET updatedAt = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_member_timestamp
    AFTER UPDATE ON member
BEGIN
    UPDATE member SET updatedAt = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_income_timestamp
    AFTER UPDATE ON income
BEGIN
    UPDATE income SET updatedAt = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_expense_timestamp
    AFTER UPDATE ON expense
BEGIN
    UPDATE expense SET updatedAt = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
