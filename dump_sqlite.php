<?php
// dump_sqlite.php
try {
    $db = new PDO('sqlite:courses.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['courses', 'students', 'members', 'enrollments', 'course_resources', 'events', 'board_members', 'site_settings', 'forms', 'submissions'];
    $data = [];

    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT * FROM $table");
            $data[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $data[$table] = []; // Table might not exist in this copy
        }
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>