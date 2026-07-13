<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Database as DatabaseConfig;
use App\Exceptions\StorageException;
use PDO;
use PDOException;

/**
 * PDO singleton wrapper. Repositories pull the shared connection via
 * Database::connection() rather than instantiating PDO themselves.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Static-only class, no instantiation.
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $config = DatabaseConfig::config();

        try {
            $pdo = new PDO(
                DatabaseConfig::dsn(),
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]
            );

            if ($config['driver'] !== 'sqlite') {
                $pdo->exec("SET NAMES '{$config['charset']}' COLLATE '{$config['collation']}'");
            }

            return $pdo;
        } catch (PDOException $e) {
            // Wrapped so ExceptionHandler can render a clean JSON error
            // instead of leaking raw PDO/connection details.
            throw new StorageException('Unable to connect to the database.', 0, $e);
        }
    }

    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connection()->commit();
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    /**
     * Run a callback inside a transaction, committing on success and
     * rolling back automatically if it throws.
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();

        try {
            $result = $callback(self::connection());
            self::commit();

            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }

    /**
     * Mainly for tests — allows forcing a fresh connection.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}