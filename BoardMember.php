<?php
require_once 'Database.php';

class BoardMember {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM board_members");
        return $stmt->fetchAll();
    }

    public function add($name, $role, $photo_url) {
        $sql = "INSERT INTO board_members (name, role, photo_url) VALUES (?, ?, ?)";
        $this->db->query($sql, [$name, $role, $photo_url]);
    }

    public function delete($id) {
        $this->db->query("DELETE FROM board_members WHERE id = ?", [$id]);
    }
}
