<?php
include_once "config.php";

class Database {
    private $conn;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        //$this->$conn->set_charset(DB_CHARSET);
    }
    
    // Input filtering helper
    public function filterInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'filterInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // Password hashing
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    // Password verification
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Build WHERE clause from array
    private function buildWhereClause($conditions) {
        if (empty($conditions)) {
            return '';
        }
        
        if (is_array($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    // Handle operators like >, <, !=, etc.
                    $operator = isset($value[1]) ? $value[1] : '=';
                    $val = $this->conn->real_escape_string($value[0]);
                    $whereParts[] = "$key $operator '$val'";
                } else {
                    $val = $this->conn->real_escape_string($value);
                    $whereParts[] = "$key = '$val'";
                }
            }
            return ' WHERE ' . implode(' AND ', $whereParts);
        } else {
            // If it's a string, use it directly
            return ' WHERE ' . $conditions;
        }
    }
    
    // Get single record
    public function getRow($table, $conditions = '', $columns = '*') {
        $sql = "SELECT $columns FROM $table";
        $sql .= $this->buildWhereClause($conditions);
        $sql .= " LIMIT 1";
        
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : false;
    }
    
    // Get multiple records
    public function getRows($table, $conditions = '', $columns = '*', $orderby = '', $limit = 0, $offset = 0) {
        $sql = "SELECT $columns FROM $table";
        $sql .= $this->buildWhereClause($conditions);
        
        if (!empty($orderby)) {
            $sql .= " ORDER BY " . $orderby;
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
            if ($offset > 0) {
                $sql .= " OFFSET " . $offset;
            }
        }
        
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Insert record
    public function insert($table, $data) {
        $filteredData = $this->filterInput($data);
        
        $columns = implode(', ', array_keys($filteredData));
        $values = [];
        foreach ($filteredData as $value) {
            $values[] = "'" . $this->conn->real_escape_string($value) . "'";
        }
        $valuesStr = implode(', ', $values);
        
        $sql = "INSERT INTO $table ($columns) VALUES ($valuesStr)";
        
        if ($this->conn->query($sql)) {
            return $this->conn->insert_id;
        }
        return $this->conn->error;
    }
    
    // Update record
    public function update($table, $data, $conditions) {
        $filteredData = $this->filterInput($data);
        
        $setParts = [];
        foreach ($filteredData as $key => $value) {
            $setParts[] = "$key = '" . $this->conn->real_escape_string($value) . "'";
        }
        $setClause = implode(', ', $setParts);
        
        $whereClause = $this->buildWhereClause($conditions);
        
        $sql = "UPDATE $table SET $setClause$whereClause";
        
        if ($this->conn->query($sql)) {
            return $this->conn->affected_rows;
        }
        return $this->conn->error;
    }
    
    // Delete record
    public function delete($table, $conditions) {
        $whereClause = $this->buildWhereClause($conditions);
        
        $sql = "DELETE FROM $table$whereClause";
        
        if ($this->conn->query($sql)) {
            return $this->conn->affected_rows;
        }
        return $this->conn->error;
    }
    
    // Count records
    public function count($table, $conditions = '') {
        $sql = "SELECT COUNT(*) as total FROM $table";
        $sql .= $this->buildWhereClause($conditions);
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }
    
    // Custom query
    public function query($sql) {
        $result = $this->conn->query($sql);
        if ($result === true) {
            return true;
        }
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return $this->conn->error;
    }
    
    // Transaction methods
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    // Get last inserted ID
    public function lastId() {
        return $this->conn->insert_id;
    }
    
    // Close connection
    public function close() {
        $this->conn->close();
    }
}
?>