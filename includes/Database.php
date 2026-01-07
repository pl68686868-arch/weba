<?php declare(strict_types=1);

/**
 * Database class - PDO wrapper for secure database operations
 * 
 * Features:
 * - Prepared statements for SQL injection protection
 * - Query caching
 * - Transaction support
 * - Error logging
 * 
 * @package Weba
 * @author Danny Duong
 */
class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    private array $queryCache = [];
    private bool $inTransaction = false;

    /**
     * Private constructor to prevent direct instantiation (Singleton pattern)
     */
    private function __construct() {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
        }
    }

    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance directly (for advanced operations)
     * 
     * @return PDO
     */
    public function getPDO(): PDO {
        return $this->pdo;
    }

    /**
     * Execute a query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     * @throws Exception
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Đã có lỗi xảy ra khi truy vấn dữ liệu.');
        }
    }

    /**
     * Fetch single row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public function fetchOne(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single column value
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a record and return last insert ID
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where WHERE clause (e.g., "id = :id")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->pdo->beginTransaction();
            return $this->inTransaction;
        }
        return false;
    }

    /**
     * Commit transaction
     * 
     * @return bool
     */
    public function commit(): bool {
        if ($this->inTransaction) {
            $result = $this->pdo->commit();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }

    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public function rollback(): bool {
        if ($this->inTransaction) {
            $result = $this->pdo->rollBack();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }

    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool
     */
    public function exists(string $table, string $where, array $params = []): bool {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        return $this->fetchColumn($sql, $params) !== false;
    }

    /**
     * Count records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (optional)
     * @param array $params Parameters
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }

    /**
     * Log database errors to file
     * 
     * @param string $message Error message
     * @return void
     */
    private function logError(string $message): void {
        $logFile = LOG_PATH . '/database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        error_log($logMessage, 3, $logFile);
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
