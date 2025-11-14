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
    echo json_encode([
        'success' => false,
        'message' => 'Invalid swap data'
    ]);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Support swapping single slots or ranges (for double periods)
    $sourceSpan = isset($source['span']) ? intval($source['span']) : 1;
    $targetSpan = isset($target['span']) ? intval($target['span']) : 1;

    // Fetch source rows for the span
    $sourcePeriods = [];
    $stmt = $db->prepare("SELECT * FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number BETWEEN ? AND ? ORDER BY period_number");
    $stmt->execute([$source['streamId'], $source['day'], $source['period'], $source['period'] + $sourceSpan - 1]);
    $sourcePeriods = $stmt->fetchAll();

    // Fetch target rows for the span
    $targetPeriods = [];
    $stmt = $db->prepare("SELECT * FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number BETWEEN ? AND ? ORDER BY period_number");
    $stmt->execute([$target['streamId'], $target['day'], $target['period'], $target['period'] + $targetSpan - 1]);
    $targetPeriods = $stmt->fetchAll();

    if (count($sourcePeriods) != $sourceSpan || count($targetPeriods) != $targetSpan) {
        throw new Exception('Period range not found or incomplete');
    }

    if ($sourceSpan !== $targetSpan) {
        // For safety, require equal spans for swaps
        throw new Exception('Cannot swap ranges of different sizes');
    }

    // Prepare update statement
    $updateStmt = $db->prepare("UPDATE timetables SET subject_id = ?, teacher_id = ?, is_double_period = ? WHERE id = ?");

    // Swap each corresponding period
    for ($i = 0; $i < $sourceSpan; $i++) {
        $s = $sourcePeriods[$i];
        $t = $targetPeriods[$i];

        // Write target into source row
        $updateStmt->execute([
            $t['subject_id'],
            $t['teacher_id'],
            $t['is_double_period'],
            $s['id']
        ]);

        // Write source into target row
        $updateStmt->execute([
            $s['subject_id'],
            $s['teacher_id'],
            $s['is_double_period'],
            $t['id']
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Periods swapped successfully'
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
