<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$source = $input['source'] ?? null;
$target = $input['target'] ?? null;

if (!$source || !$target) {
    echo json_encode(['hasConflict' => false]);
    exit;
}

$db = getDB();

// Check if the swap would cause a teacher conflict
$hasConflict = false;

// Get teacher IDs
$sourceTeacherId = $source['teacherId'] ?? null;
$targetTeacherId = $target['teacherId'] ?? null;

if ($sourceTeacherId && $targetTeacherId) {
    // Check if source teacher is already teaching at target time
    $stmt = $db->prepare("SELECT COUNT(*) FROM timetables
                          WHERE teacher_id = ?
                          AND day_of_week = ?
                          AND period_number = ?
                          AND stream_id != ?");
    $stmt->execute([$sourceTeacherId, $target['day'], $target['period'], $source['streamId']]);

    if ($stmt->fetchColumn() > 0) {
        $hasConflict = true;
    }

    // Check if target teacher is already teaching at source time
    $stmt = $db->prepare("SELECT COUNT(*) FROM timetables
                          WHERE teacher_id = ?
                          AND day_of_week = ?
                          AND period_number = ?
                          AND stream_id != ?");
    $stmt->execute([$targetTeacherId, $source['day'], $source['period'], $target['streamId']]);

    if ($stmt->fetchColumn() > 0) {
        $hasConflict = true;
    }
}

echo json_encode(['hasConflict' => $hasConflict]);
