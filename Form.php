<?php
class Form
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM forms ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->query("SELECT * FROM forms WHERE id = ?", [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    public function create($title, $description, $fields_json)
    {
        $sql = "INSERT INTO forms (title, description, fields_json) VALUES (?, ?, ?)";
        $this->db->query($sql, [$title, $description, $fields_json]);
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM forms WHERE id = ?", [$id]);
        $this->db->query("DELETE FROM submissions WHERE form_id = ?", [$id]);
    }

    public function submit($formId, $data)
    {
        $json = json_encode($data);
        $this->db->query("INSERT INTO submissions (form_id, data_json) VALUES (?, ?)", [$formId, $json]);
    }

    public function getSubmissions($formId)
    {
        $stmt = $this->db->query("SELECT * FROM submissions WHERE form_id = ? ORDER BY submitted_at DESC", [$formId]);
        return $stmt->fetchAll();
    }
}
