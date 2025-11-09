<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$teacherId = intval($_GET['teacher_id'] ?? 0);
$subjectId = intval($_GET['subject_id'] ?? 0);

if ($teacherId <= 0 || $subjectId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT stream_id FROM subject_assignments WHERE teacher_id = ? AND subject_id = ?");
$stmt->execute([$teacherId, $subjectId]);
$assignments = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'stream_ids' => $assignments
]);
?>
