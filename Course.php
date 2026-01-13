<?php

require_once 'Database.php';

class Course {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM courses ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO courses (title, description, instructor, duration, thumbnail) VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['title'],
            $data['description'],
            $data['instructor'],
            $data['duration'],
            $data['thumbnail'] ?? 'https://via.placeholder.com/300x200'
        ]);
        return $this->db->lastInsertId();
    }

    public function delete($id) {
        $sql = "DELETE FROM courses WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function get($id) {
        $sql = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $sql = "UPDATE courses SET title = ?, description = ?, instructor = ?, duration = ?, thumbnail = ?, content = ? WHERE id = ?";
        $this->db->query($sql, [
            $data['title'],
            $data['description'],
            $data['instructor'],
            $data['duration'],
            $data['thumbnail'],
            $data['content'],
            $id
        ]);
    }

    public function updateContent($id, $content) {
        $sql = "UPDATE courses SET content = ? WHERE id = ?";
        $this->db->query($sql, [$content, $id]);
    }
}
