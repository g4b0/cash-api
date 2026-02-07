<?php

declare(strict_types=1);

$dbPath = __DIR__ . '/cash.db';
$schemaPath = __DIR__ . '/schema.sql';

if (file_exists($dbPath)) {
    echo "Database already exists at {$dbPath}\n";
    echo "Delete it first if you want to reinitialize.\n";
    exit(1);
}

if (!file_exists($schemaPath)) {
    echo "Schema file not found at {$schemaPath}\n";
    exit(1);
}

$schema = file_get_contents($schemaPath);

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    $db->exec($schema);
    $db->close();

    echo "Database initialized at {$dbPath}\n";
} catch (Exception $e) {
    // Clean up partial DB file on failure
    if (file_exists($dbPath)) {
        unlink($dbPath);
    }

    echo "Error initializing database: {$e->getMessage()}\n";
    exit(1);
}
