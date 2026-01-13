<?php
require_once 'Database.php';

class Site {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function get($key) {
        $stmt = $this->db->query("SELECT value FROM site_settings WHERE key = ?", [$key]);
        $res = $stmt->fetch();
        return $res ? $res['value'] : '';
    }

    public function set($key, $value) {
        $this->db->query("CREATE TABLE IF NOT EXISTS site_settings (key TEXT PRIMARY KEY, value TEXT)"); // Ensure table exists
        $sql = "INSERT INTO site_settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value";
        $this->db->query($sql, [$key, $value]);
    }

    // Static helper for easy access in views if needed (requires instantiation pattern, but we'll stick to instance for consistency with Course model or simple usage)
}
