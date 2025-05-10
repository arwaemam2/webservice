<?php
class MySQLHandler {
    private $conn;

    public function __construct($table) {
        $this->table = $table;
    }

    public function connect() {
        $this->conn = new mysqli("localhost", "root", "", "glass_shop");
        if ($this->conn->connect_error) {
            return false;
        }
        return true;
    }

    public function select($id) {
        $sql = "SELECT * FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function insert($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $values = array_values($data);
        $types = str_repeat("s", count($data)); 
        $sql = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $set = [];
        $values = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $values[] = $value;
        }
        $set = implode(", ", $set);
        $values[] = $id;
        $sql = "UPDATE $this->table SET $set WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(str_repeat("s", count($data)) . "i", ...$values);
        return $stmt->execute();
    }

    public function delete($id) {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}