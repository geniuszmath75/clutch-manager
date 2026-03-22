<?php

declare(strict_types=1);

namespace core;

use http\Exception\RuntimeException;
use PDO;
use PDOException;

/**
 * Database - a singleton managing the PDO connection.
 *
 * A single connection for the entire HTTP request lifecycle.
 * All queries must use prepared statements (enforced by the repository).
 */
final class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo {
        get {
            return $this->pdo;
        }
    }

    public function __construct()
    {
        $host = $this->requireEnv('DB_HOST');
        $port = $this->requireEnv('DB_PORT');
        $dbname = $this->requireEnv('DB_NAME');
        $user = $this->requireEnv('DB_USER');
        $pass = $this->requireEnv('DB_PASSWORD');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("[ERROR]: Database connection failed.", 0, $e);
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Transaction helper - automatically rollbacks on exception.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function requireEnv(string $key): string
    {
        $value = $_ENV[$key] ?? null;

        if (empty($value)) {
            throw new RuntimeException("[ERROR]: Missing required environment variable {$key}");
        }

        return $value;
    }

    // Block singleton cloning and deserialization
    public function __clone() {}

    public function __wakeup(): never
    {
        throw new RuntimeException("[ERROR]: Cannot deserialize a singleton Database.");
    }
}