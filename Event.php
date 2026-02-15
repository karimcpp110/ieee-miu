<?php
class Event
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM events ORDER BY event_date DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getUpcoming()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getNearestUpcoming()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 1");
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    public function getPast()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM events WHERE event_date < NOW() ORDER BY event_date DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGallery($eventId)
    {
        try {
            $stmt = $this->db->query("SELECT * FROM event_gallery WHERE event_id = ? ORDER BY created_at DESC", [$eventId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllPastGallery()
    {
        try {
            $stmt = $this->db->query("SELECT eg.*, e.title as event_title FROM event_gallery eg JOIN events e ON eg.event_id = e.id WHERE e.event_date < NOW() ORDER BY eg.created_at DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getShuffleGallery($limit = 10)
    {
        try {
            // Fix: LIMIT doesn't always accept placeholders as strings in some MySQL/PDO setups
            // We cast to int and use it directly since it's an internal call
            $limit = (int) $limit;
            $stmt = $this->db->query("SELECT * FROM event_gallery ORDER BY RAND() LIMIT $limit");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllGalleryGrouped()
    {
        try {
            $stmt = $this->db->query("SELECT e.id as event_id, e.title as event_title, eg.image_path, eg.id as photo_id 
                                    FROM events e 
                                    JOIN event_gallery eg ON e.id = eg.event_id 
                                    ORDER BY e.event_date DESC, eg.created_at DESC");
            $rows = $stmt->fetchAll();
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row['event_title']][] = $row;
            }
            return $grouped;
        } catch (Exception $e) {
            return [];
        }
    }

    public function addGalleryImage($eventId, $imagePath)
    {
        try {
            $sql = "INSERT INTO event_gallery (event_id, image_path) VALUES (?, ?)";
            $this->db->query($sql, [$eventId, $imagePath]);
        } catch (Exception $e) {
            // Log error
        }
    }

    public function deleteGalleryImage($id)
    {
        try {
            $stmt = $this->db->query("SELECT image_path FROM event_gallery WHERE id = ?", [$id]);
            $res = $stmt->fetch();
            if ($res && file_exists($res['image_path'])) {
                unlink($res['image_path']);
            }
            $this->db->query("DELETE FROM event_gallery WHERE id = ?", [$id]);
        } catch (Exception $e) {
            // Log error
        }
    }

    public function add($title, $category, $description, $event_date, $image_path)
    {
        try {
            // Handle 'T' separator from datetime-local input safely
            $event_date = str_replace('T', ' ', $event_date);
            $sql = "INSERT INTO events (title, category, description, event_date, image_path) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, [$title, $category, $description, $event_date, $image_path]);
        } catch (Exception $e) {
            throw $e; // Re-throw to show error or handle in dashboard
        }
    }

    public function delete($id)
    {
        try {
            // Delete gallery images first
            $stmt = $this->db->query("SELECT image_path FROM event_gallery WHERE event_id = ?", [$id]);
            $gallery = $stmt->fetchAll();
            foreach ($gallery as $img) {
                if (file_exists($img['image_path']))
                    unlink($img['image_path']);
            }
            $this->db->query("DELETE FROM event_gallery WHERE event_id = ?", [$id]);

            // Delete main event image
            $stmt = $this->db->query("SELECT image_path FROM events WHERE id = ?", [$id]);
            $res = $stmt->fetch();
            if ($res && file_exists($res['image_path']) && strpos($res['image_path'], 'placeholder') === false) {
                unlink($res['image_path']);
            }

            $this->db->query("DELETE FROM events WHERE id = ?", [$id]);
        } catch (Exception $e) {
            // Log error
        }
    }
}
