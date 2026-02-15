<?php
require_once 'Database.php';

class Site
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function get($key)
    {
        try {
            $stmt = $this->db->query("SELECT value FROM site_settings WHERE key_name = ?", [$key]);
            $res = $stmt->fetch();
            return $res ? $res['value'] : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function set($key, $value)
    {
        $stmt = $this->db->query("SELECT key_name FROM site_settings WHERE key_name = ?", [$key]);
        if ($stmt->fetch()) {
            $this->db->query("UPDATE site_settings SET value = ? WHERE key_name = ?", [$value, $key]);
        } else {
            $this->db->query("INSERT INTO site_settings (key_name, value) VALUES (?, ?)", [$key, $value]);
        }
    }

    // Static helper for easy access in views if needed (requires instantiation pattern, but we'll stick to instance for consistency with Course model or simple usage)
}
