<?php
class GalleryModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Sections (Albums)
    public function createSection($title, $description = '')
    {
        try {
            $sql = "INSERT INTO gallery_sections (title, description) VALUES (?, ?)";
            $this->db->query($sql, [$title, $description]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAllSections()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM gallery_sections ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteSection($id)
    {
        try {
            // Delete actual files first
            $stmt = $this->db->query("SELECT image_path FROM gallery_photos WHERE section_id = ?", [$id]);
            $photos = $stmt->fetchAll();
            foreach ($photos as $p) {
                if (file_exists($p['image_path']))
                    unlink($p['image_path']);
            }

            $this->db->query("DELETE FROM gallery_photos WHERE section_id = ?", [$id]);
            $this->db->query("DELETE FROM gallery_sections WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Photos
    public function addPhoto($sectionId, $imagePath)
    {
        try {
            $sql = "INSERT INTO gallery_photos (section_id, image_path) VALUES (?, ?)";
            $this->db->query($sql, [$sectionId, $imagePath]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPhotosBySection($sectionId)
    {
        try {
            $stmt = $this->db->query("SELECT * FROM gallery_photos WHERE section_id = ? ORDER BY created_at DESC", [$sectionId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function deletePhoto($id)
    {
        try {
            $stmt = $this->db->query("SELECT image_path FROM gallery_photos WHERE id = ?", [$id]);
            $res = $stmt->fetch();
            if ($res && file_exists($res['image_path']))
                unlink($res['image_path']);

            $this->db->query("DELETE FROM gallery_photos WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Global Feed (for Index Mosaic)
    public function getGlobalShuffle($limit = 20)
    {
        try {
            // Combine both event gallery and standalone photos
            $sql = "(SELECT image_path, 'event' as source FROM event_gallery)
                    UNION ALL
                    (SELECT image_path, 'standalone' as source FROM gallery_photos)
                    ORDER BY RAND() LIMIT " . (int) $limit;
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}