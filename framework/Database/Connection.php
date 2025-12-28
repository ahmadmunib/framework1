<?php

declare(strict_types=1);

namespace Framework\Database;

use PDO;
use PDOStatement;
use PDOException;

/**
 * Database Connection Manager
 * 
 * Manages PDO database connections with support for multiple connections.
 * 
 * @example
 * DB::connection()->query('SELECT * FROM users');
 * DB::table('users')->where('active', 1)->get();
 */
class Connection
{
    /**
     * Active PDO connections
     */
    protected static array $connections = [];

    /**
     * Default connection name
     */
    protected static string $defaultConnection = 'default';

    /**
     * Query log
     */
    protected static array $queryLog = [];

    /**
     * Whether to log queries
     */
    protected static bool $logging = false;

    /**
     * Get a database connection
     */
    public static function connection(?string $name = null): PDO
    {
        $name = $name ?? self::getDefaultConnection();

        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::createConnection($name);
        }

        return self::$connections[$name];
    }

    /**
     * Create a new PDO connection
     */
    protected static function createConnection(string $name): PDO
    {
        $config = self::getConfig($name);

        if (!$config) {
            throw new PDOException("Database connection [{$name}] not configured.");
        }

        $driver = $config['driver'] ?? 'mysql';
        $dsn = self::buildDsn($driver, $config);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Add MySQL-specific options
        if ($driver === 'mysql') {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$config['charset']}' COLLATE '{$config['collation']}'";
        }

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $options
        );

        return $pdo;
    }

    /**
     * Build the DSN string
     */
    protected static function buildDsn(string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? 'localhost',
                $config['port'] ?? 3306,
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'] ?? 'localhost',
                $config['port'] ?? 5432,
                $config['database'] ?? ''
            ),
            'sqlite' => sprintf('sqlite:%s', $config['database'] ?? ':memory:'),
            default => throw new PDOException("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Get connection configuration
     */
    protected static function getConfig(string $name): ?array
    {
        // Try to get from Config class if available
        if (class_exists('Framework\Core\Config')) {
            $default = \Framework\Core\Config::get('database.default', 'mysql');
            
            if ($name === 'default') {
                $name = $default;
            }
            
            return \Framework\Core\Config::get("database.connections.{$name}");
        }

        return null;
    }

    /**
     * Get default connection name
     */
    public static function getDefaultConnection(): string
    {
        if (class_exists('Framework\Core\Config')) {
            return \Framework\Core\Config::get('database.default', 'mysql');
        }
        return self::$defaultConnection;
    }

    /**
     * Set default connection name
     */
    public static function setDefaultConnection(string $name): void
    {
        self::$defaultConnection = $name;
    }

    /**
     * Execute a raw SQL query
     */
    public static function query(string $sql, array $bindings = [], ?string $connection = null): PDOStatement
    {
        $pdo = self::connection($connection);
        
        self::logQuery($sql, $bindings);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt;
    }

    /**
     * Execute a SELECT query and return all results
     */
    public static function select(string $sql, array $bindings = [], ?string $connection = null): array
    {
        return self::query($sql, $bindings, $connection)->fetchAll();
    }

    /**
     * Execute a SELECT query and return first result
     */
    public static function selectOne(string $sql, array $bindings = [], ?string $connection = null): ?object
    {
        $result = self::query($sql, $bindings, $connection)->fetch();
        return $result ?: null;
    }

    /**
     * Execute an INSERT query and return last insert ID
     */
    public static function insert(string $sql, array $bindings = [], ?string $connection = null): int
    {
        self::query($sql, $bindings, $connection);
        return (int) self::connection($connection)->lastInsertId();
    }

    /**
     * Execute an UPDATE query and return affected rows
     */
    public static function update(string $sql, array $bindings = [], ?string $connection = null): int
    {
        return self::query($sql, $bindings, $connection)->rowCount();
    }

    /**
     * Execute a DELETE query and return affected rows
     */
    public static function delete(string $sql, array $bindings = [], ?string $connection = null): int
    {
        return self::query($sql, $bindings, $connection)->rowCount();
    }

    /**
     * Execute a statement (no return value expected)
     */
    public static function statement(string $sql, array $bindings = [], ?string $connection = null): bool
    {
        return self::query($sql, $bindings, $connection)->rowCount() >= 0;
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(?string $connection = null): bool
    {
        return self::connection($connection)->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(?string $connection = null): bool
    {
        return self::connection($connection)->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(?string $connection = null): bool
    {
        return self::connection($connection)->rollBack();
    }

    /**
     * Execute callback within a transaction
     */
    public static function transaction(callable $callback, ?string $connection = null): mixed
    {
        self::beginTransaction($connection);

        try {
            $result = $callback(self::connection($connection));
            self::commit($connection);
            return $result;
        } catch (\Throwable $e) {
            self::rollback($connection);
            throw $e;
        }
    }

    /**
     * Create a new QueryBuilder instance
     */
    public static function table(string $table, ?string $connection = null): QueryBuilder
    {
        return (new QueryBuilder($connection))->table($table);
    }

    /**
     * Enable query logging
     */
    public static function enableQueryLog(): void
    {
        self::$logging = true;
    }

    /**
     * Disable query logging
     */
    public static function disableQueryLog(): void
    {
        self::$logging = false;
    }

    /**
     * Get the query log
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * Clear the query log
     */
    public static function flushQueryLog(): void
    {
        self::$queryLog = [];
    }

    /**
     * Log a query
     */
    protected static function logQuery(string $sql, array $bindings): void
    {
        if (self::$logging) {
            self::$queryLog[] = [
                'query' => $sql,
                'bindings' => $bindings,
                'time' => microtime(true),
            ];
        }
    }

    /**
     * Disconnect from a connection
     */
    public static function disconnect(?string $name = null): void
    {
        $name = $name ?? self::getDefaultConnection();
        unset(self::$connections[$name]);
    }

    /**
     * Disconnect from all connections
     */
    public static function disconnectAll(): void
    {
        self::$connections = [];
    }

    /**
     * Reconnect to a connection
     */
    public static function reconnect(?string $name = null): PDO
    {
        self::disconnect($name);
        return self::connection($name);
    }

    /**
     * Get raw PDO instance
     */
    public static function getPdo(?string $name = null): PDO
    {
        return self::connection($name);
    }
}
