<?php

namespace App\Database\Repository;

use PDO;

/**
 * Abstract base repository providing common database access.
 */
abstract class Repository
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
}
