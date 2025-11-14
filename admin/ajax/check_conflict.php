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

// Support single-slot swaps and multi-slot (span) swaps
$sourceSpan = isset($source['span']) ? intval($source['span']) : 1;
$targetSpan = isset($target['span']) ? intval($target['span']) : 1;

// Fetch teacher ids for source range from DB (more reliable than trusting client data)
$sourceTeacherIds = [];
for ($i = 0; $i < $sourceSpan; $i++) {
    $p = intval($source['period']) + $i;
    $stmt = $db->prepare("SELECT teacher_id FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number = ?");
    $stmt->execute([$source['streamId'], $source['day'], $p]);
    $row = $stmt->fetch();
    $sourceTeacherIds[] = $row ? $row['teacher_id'] : null;
}

// Fetch teacher ids for target range
$targetTeacherIds = [];
for ($i = 0; $i < $targetSpan; $i++) {
    $p = intval($target['period']) + $i;
    $stmt = $db->prepare("SELECT teacher_id FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number = ?");
    $stmt->execute([$target['streamId'], $target['day'], $p]);
    $row = $stmt->fetch();
    $targetTeacherIds[] = $row ? $row['teacher_id'] : null;
}

// Now check for conflicts: for each source teacher, ensure they are not scheduled at the corresponding target slot in other streams
for ($i = 0; $i < max($sourceSpan, $targetSpan); $i++) {
    $sTeacher = $sourceTeacherIds[$i] ?? null;
    $tPeriod = intval($target['period']) + $i;
    if ($sTeacher) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM timetables WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND stream_id != ?");
        $stmt->execute([$sTeacher, $target['day'], $tPeriod, $source['streamId']]);
        if ($stmt->fetchColumn() > 0) {
            $hasConflict = true;
            break;
        }
    }

    // Check target teachers at source slots
    $tTeacher = $targetTeacherIds[$i] ?? null;
    $sPeriod = intval($source['period']) + $i;
    if ($tTeacher) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM timetables WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND stream_id != ?");
        $stmt->execute([$tTeacher, $source['day'], $sPeriod, $target['streamId']]);
        if ($stmt->fetchColumn() > 0) {
            $hasConflict = true;
            break;
        }
    }
}

echo json_encode(['hasConflict' => $hasConflict]);
