<?php
require_once 'Database.php';

class Site
{
    private $db;
    private $cache;
    private $hasCache;

    public function __construct()
    {
        $this->db = new Database();
        $this->hasCache = file_exists('Cache.php');
        if ($this->hasCache) {
            require_once 'Cache.php';
            $this->cache = new Cache();
        }
    }

    public function get($key)
    {
        if ($this->hasCache) {
            $cached = $this->cache->get('site_setting_' . $key);
            if ($cached !== null)
                return $cached;
        }

        try {
            $stmt = $this->db->query("SELECT value FROM site_settings WHERE key_name = ?", [$key]);
            $res = $stmt->fetch();
            $val = $res ? $res['value'] : '';
            if ($this->hasCache) {
                $this->cache->set('site_setting_' . $key, $val);
            }
            return $val;
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

        if ($this->hasCache) {
            $this->cache->delete('site_setting_' . $key);
        }
    }
}
?>