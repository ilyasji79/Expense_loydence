<?php
/**
 * Database Connection (PDO)
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    // Execute query with prepared statement
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query Error: " . $e->getMessage());
        }
    }

    // Fetch single row
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }

    // Row count
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}

// Initialize database connection
$db = new Database();

