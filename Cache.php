<?php
class Cache
{
    private $cacheDir = 'cache/';
    private $expiry = 3600; // 1 hour

    public function __construct($expiry = 3600)
    {
        $this->expiry = $expiry;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get($key)
    {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename) && (time() - filemtime($filename) < $this->expiry)) {
            return unserialize(file_get_contents($filename));
        }
        return null;
    }

    public function set($key, $data)
    {
        $filename = $this->cacheDir . md5($key) . '.cache';
        file_put_contents($filename, serialize($data));
    }

    public function delete($key)
    {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function clear()
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
?>