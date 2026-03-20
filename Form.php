<?php
class Form
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll($type = null)
    {
        try {
            if ($type) {
                $stmt = $this->db->query("SELECT * FROM forms WHERE type = ? ORDER BY created_at DESC", [$type]);
            } else {
                $stmt = $this->db->query("SELECT * FROM forms ORDER BY created_at DESC");
            }
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

    public function create($title, $description, $fields_json, $type = 'general')
    {
        $sql = "INSERT INTO forms (title, description, fields_json, type) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$title, $description, $fields_json, $type]);
    }

    public function update($id, $title, $description, $fields_json)
    {
        $sql = "UPDATE forms SET title = ?, description = ?, fields_json = ? WHERE id = ?";
        $this->db->query($sql, [$title, $description, $fields_json, $id]);
    }

    public function updateAutomation($id, $subject, $template, $delay_hours = 0, $conditions = null)
    {
        $sql = "UPDATE forms SET automation_email_subject = ?, automation_email_template = ?, automation_delay_hours = ?, automation_conditions = ? WHERE id = ?";
        $this->db->query($sql, [$subject, $template, $delay_hours, $conditions, $id]);
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM forms WHERE id = ?", [$id]);
        $this->db->query("DELETE FROM submissions WHERE form_id = ?", [$id]);
    }

    public function submit($formId, $data, $studentId = null)
    {
        $json = json_encode($data);
        $this->db->query("INSERT INTO submissions (form_id, data_json, student_id) VALUES (?, ?, ?)", [$formId, $json, $studentId]);
    }

    public function getSubmissions($formId)
    {
        $stmt = $this->db->query("SELECT * FROM submissions WHERE form_id = ? ORDER BY submitted_at DESC", [$formId]);
        return $stmt->fetchAll();
    }

    public function updateSubmission($id, $status, $notes)
    {
        try {
            return $this->db->query("UPDATE submissions SET status = ?, admin_notes = ? WHERE id = ?", [$status, $notes, $id]);
        } catch (Exception $e) {
            error_log("Update submission failed: " . $e->getMessage());
            return false;
        }
    }
}
