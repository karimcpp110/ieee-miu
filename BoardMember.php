<?php
require_once 'Database.php';

class BoardMember {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM board_members ORDER BY CASE 
            WHEN committee = 'Board' THEN 1
            WHEN committee = 'PR' THEN 2
            WHEN committee = 'HR' THEN 3
            WHEN committee = 'multi media' THEN 4
            WHEN committee = 'R&D' THEN 5
            WHEN committee = 'technical' THEN 6
            WHEN committee = 'event planning' THEN 7
            ELSE 8 END");
        return $stmt->fetchAll();
    }

    public function add($name, $role, $photo_url, $committee = 'Board') {
        $sql = "INSERT INTO board_members (name, role, photo_url, committee) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$name, $role, $photo_url, $committee]);
    }

    public function delete($id) {
        $this->db->query("DELETE FROM board_members WHERE id = ?", [$id]);
    }

    public function update($id, $name, $role, $photo_url, $committee = 'Board') {
        $sql = "UPDATE board_members SET name = ?, role = ?, photo_url = ?, committee = ? WHERE id = ?";
        $this->db->query($sql, [$name, $role, $photo_url, $committee, $id]);
    }
}
