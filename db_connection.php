<?php
// includes/db_connection.php - FIXED VERSION

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            // Display error and stop execution
            die("<div style='background:#f8d7da;color:#721c24;padding:20px;border-radius:5px;margin:20px;font-family:Arial;'>
                <h2>Database Connection Error</h2>
                <p><strong>Error:</strong> " . $e->getMessage() . "</p>
                <p><strong>Please check:</strong></p>
                <ul>
                    <li>MySQL is running in XAMPP</li>
                    <li>Database '<strong>" . DB_NAME . "</strong>' exists in phpMyAdmin</li>
                    <li>Username and password are correct in config.php</li>
                </ul>
                <p><a href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a></p>
            </div>");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        return $stmt;
    }

    public function getOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
        $values = array_values($data);
        
        $stmt = $this->query($sql, $values);
        return $this->connection->insert_id;
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        $values = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE $where";
        $values = array_merge($values, $whereParams);
        
        $stmt = $this->query($sql, $values);
        return $this->connection->affected_rows;
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function close() {
        $this->connection->close();
    }
}
?>