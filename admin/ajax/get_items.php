<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireLogin();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$db = getDB();

switch ($type) {
    case 'forms':
        $stmt = $db->query("SELECT id, name FROM forms ORDER BY name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo '<div class="alert alert-info">No forms found. <a href="forms.php">Add a form</a></div>';
        } else {
            echo '<div class="table-responsive"><table class="table table-hover">';
            echo '<thead><tr><th>Form Name</th><th>Actions</th></tr></thead><tbody>';
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                echo '<td>';
                echo '<a href="forms.php" class="btn btn-sm btn-outline-primary">Edit</a> ';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        break;

    case 'streams':
        $stmt = $db->query("SELECT st.id, st.name, f.name as form_name FROM streams st JOIN forms f ON st.form_id = f.id ORDER BY f.name, st.name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo '<div class="alert alert-info">No streams found. <a href="forms.php">Add a stream</a></div>';
        } else {
            echo '<div class="table-responsive"><table class="table table-hover">';
            echo '<thead><tr><th>Form</th><th>Stream Name</th><th>Actions</th></tr></thead><tbody>';
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($item['form_name']) . '</td>';
                echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                echo '<td>';
                echo '<a href="forms.php" class="btn btn-sm btn-outline-primary">Edit</a> ';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        break;

    case 'subjects':
        $stmt = $db->query("SELECT id, name, code FROM subjects ORDER BY name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo '<div class="alert alert-info">No subjects found. <a href="subjects.php">Add a subject</a></div>';
        } else {
            echo '<div class="table-responsive"><table class="table table-hover">';
            echo '<thead><tr><th>Subject Code</th><th>Subject Name</th><th>Actions</th></tr></thead><tbody>';
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($item['code']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                echo '<td>';
                echo '<a href="subjects.php" class="btn btn-sm btn-outline-primary">Edit</a> ';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        break;

    case 'teachers':
        $stmt = $db->query("SELECT id, first_name, last_name, email FROM teachers ORDER BY first_name, last_name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo '<div class="alert alert-info">No teachers found. <a href="teachers.php">Add a teacher</a></div>';
        } else {
            echo '<div class="table-responsive"><table class="table table-hover">';
            echo '<thead><tr><th>Full Name</th><th>Email</th><th>Actions</th></tr></thead><tbody>';
            foreach ($items as $item) {
                $fullName = htmlspecialchars($item['first_name'] . ' ' . $item['last_name']);
                echo '<tr>';
                echo '<td><strong>' . $fullName . '</strong></td>';
                echo '<td>' . htmlspecialchars($item['email']) . '</td>';
                echo '<td>';
                echo '<a href="teachers.php" class="btn btn-sm btn-outline-primary">Edit</a> ';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        break;

    case 'assignments':
        $stmt = $db->query("SELECT 
                            sa.id,
                            t.first_name, t.last_name,
                            s.name as subject_name, s.code as subject_code,
                            st.name as stream_name,
                            f.name as form_name
                            FROM subject_assignments sa
                            JOIN teachers t ON sa.teacher_id = t.id
                            JOIN subjects s ON sa.subject_id = s.id
                            JOIN streams st ON sa.stream_id = st.id
                            JOIN forms f ON st.form_id = f.id
                            ORDER BY f.name, st.name, s.name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo '<div class="alert alert-info">No assignments found. <a href="assignments.php">Create an assignment</a></div>';
        } else {
            echo '<div class="table-responsive"><table class="table table-hover">';
            echo '<thead><tr><th>Form</th><th>Stream</th><th>Subject</th><th>Teacher</th><th>Actions</th></tr></thead><tbody>';
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($item['form_name']) . '</td>';
                echo '<td>' . htmlspecialchars($item['stream_name']) . '</td>';
                echo '<td>' . htmlspecialchars($item['subject_code']) . ' - ' . htmlspecialchars($item['subject_name']) . '</td>';
                echo '<td><strong>' . htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) . '</strong></td>';
                echo '<td>';
                echo '<a href="assignments.php" class="btn btn-sm btn-outline-primary">Edit</a> ';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        break;

    default:
        echo '<div class="alert alert-warning">Invalid item type.</div>';
}
?>
