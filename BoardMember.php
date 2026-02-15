<?php
require_once 'Database.php';

class BoardMember
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll()
    {
        try {
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
        } catch (Exception $e) {
            return [];
        }
    }

    public function getFeatured()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM board_members WHERE is_best = 1 ORDER BY name ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function add($name, $role, $photo_url, $committee = 'Board', $bio = '', $linkedin_url = '')
    {
        $sql = "INSERT INTO board_members (name, role, photo_url, committee, bio, linkedin_url) VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [$name, $role, $photo_url, $committee, $bio, $linkedin_url]);
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM board_members WHERE id = ?", [$id]);
    }

    public function update($id, $name, $role, $photo_url, $committee = 'Board', $bio = '', $linkedin_url = '')
    {
        $sql = "UPDATE board_members SET name = ?, role = ?, photo_url = ?, committee = ?, bio = ?, linkedin_url = ? WHERE id = ?";
        $this->db->query($sql, [$name, $role, $photo_url, $committee, $bio, $linkedin_url, $id]);
    }

    public function setBest($id, $isBest)
    {
        $sql = "UPDATE board_members SET is_best = ? WHERE id = ?";
        $this->db->query($sql, [$isBest ? 1 : 0, $id]);
    }
}
