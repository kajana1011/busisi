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

// Enforce swaps only within the same stream
if ((string)$source['streamId'] !== (string)$target['streamId']) {
    echo json_encode([
        'success' => false,
        'message' => 'Swaps between different streams are not allowed.'
    ]);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Support swapping single slots or ranges (for double periods)
    $sourceSpan = isset($source['span']) ? intval($source['span']) : 1;
    $targetSpan = isset($target['span']) ? intval($target['span']) : 1;

    // Cast all numeric inputs to int for safety
    $sourceStreamId = intval($source['streamId']);
    $sourceDay = intval($source['day']);
    $sourcePeriod = intval($source['period']);
    $targetStreamId = intval($target['streamId']);
    $targetDay = intval($target['day']);
    $targetPeriod = intval($target['period']);

    // Fetch source rows for the span
    $sourcePeriods = [];
    $stmt = $db->prepare("SELECT * FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number BETWEEN ? AND ? ORDER BY period_number");
    $stmt->execute([$sourceStreamId, $sourceDay, $sourcePeriod, $sourcePeriod + $sourceSpan - 1]);
    $sourcePeriods = $stmt->fetchAll();

    // Fetch target rows for the span
    $targetPeriods = [];
    $stmt = $db->prepare("SELECT * FROM timetables WHERE stream_id = ? AND day_of_week = ? AND period_number BETWEEN ? AND ? ORDER BY period_number");
    $stmt->execute([$targetStreamId, $targetDay, $targetPeriod, $targetPeriod + $targetSpan - 1]);
    $targetPeriods = $stmt->fetchAll();

    if (count($sourcePeriods) != $sourceSpan || count($targetPeriods) != $targetSpan) {
        throw new Exception('Period range not found or incomplete. Source found: ' . count($sourcePeriods) . '/' . $sourceSpan . ', Target found: ' . count($targetPeriods) . '/' . $targetSpan . '. Query: source_stream=' . $sourceStreamId . ' day=' . $sourceDay . ' period=' . $sourcePeriod . '-' . ($sourcePeriod + $sourceSpan - 1));
    }

    if ($sourceSpan !== $targetSpan) {
        // For safety, require equal spans for swaps
        throw new Exception('Cannot swap ranges of different sizes');
    }

    // Validate that source and target have matching double-period status
    // A double period has span=2, single has span=1
    // They must match: double can only swap with double, single with single
    if ($sourceSpan === 1 && $targetSpan === 1) {
        // Both are single periods - this is OK, no additional validation needed
    } elseif ($sourceSpan === 2 && $targetSpan === 2) {
        // Both are double periods - this is OK, validate they're both marked as double
        if (empty($sourcePeriods[0]['is_double_period']) || empty($targetPeriods[0]['is_double_period'])) {
            throw new Exception('Span mismatch detected: double swaps require both first periods to be marked as double');
        }
    } else {
        // One is single and other is double - NOT ALLOWED
        throw new Exception('Cannot swap single and double periods. They must be the same type.');
    }

    // Prepare update statement
    $updateStmt = $db->prepare("UPDATE timetables SET subject_id = ?, teacher_id = ?, is_double_period = ? WHERE id = ?");

    // Swap each corresponding period
    for ($i = 0; $i < $sourceSpan; $i++) {
        $s = $sourcePeriods[$i];
        $t = $targetPeriods[$i];

        // For double periods: is_double_period flag should only be set on the FIRST slot
        // When swapping, we preserve the double flag structure:
        // - Slot 0 of a span gets is_double_period based on whether span > 1
        // - Slot 1+ of a span always gets is_double_period = 0
        $targetDoubleFlag = ($sourceSpan > 1 && $i === 0) ? 1 : 0;
        $sourceDoubleFlag = ($targetSpan > 1 && $i === 0) ? 1 : 0;

        // Write target into source row
        $updateStmt->execute([
            $t['subject_id'],
            $t['teacher_id'],
            $targetDoubleFlag,
            $s['id']
        ]);

        // Write source into target row
        $updateStmt->execute([
            $s['subject_id'],
            $s['teacher_id'],
            $sourceDoubleFlag,
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
