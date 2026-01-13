<?php
class Event {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM events ORDER BY event_date ASC");
        return $stmt->fetchAll();
    }

    public function add($title, $description, $event_date, $image_path) {
        $sql = "INSERT INTO events (title, description, event_date, image_path) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$title, $description, $event_date, $image_path]);
    }

    public function delete($id) {
        $this->db->query("DELETE FROM events WHERE id = ?", [$id]);
    }
}
