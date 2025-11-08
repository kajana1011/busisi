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

    // Get source period details
    $stmt = $db->prepare("SELECT * FROM timetables
                          WHERE stream_id = ?
                          AND day_of_week = ?
                          AND period_number = ?");
    $stmt->execute([$source['streamId'], $source['day'], $source['period']]);
    $sourcePeriod = $stmt->fetch();

    // Get target period details
    $stmt = $db->prepare("SELECT * FROM timetables
                          WHERE stream_id = ?
                          AND day_of_week = ?
                          AND period_number = ?");
    $stmt->execute([$target['streamId'], $target['day'], $target['period']]);
    $targetPeriod = $stmt->fetch();

    if (!$sourcePeriod || !$targetPeriod) {
        throw new Exception('Period not found');
    }

    // Swap the periods
    $stmt = $db->prepare("UPDATE timetables
                          SET subject_id = ?, teacher_id = ?, is_double_period = ?
                          WHERE id = ?");

    // Update source with target data
    $stmt->execute([
        $targetPeriod['subject_id'],
        $targetPeriod['teacher_id'],
        $targetPeriod['is_double_period'],
        $sourcePeriod['id']
    ]);

    // Update target with source data
    $stmt->execute([
        $sourcePeriod['subject_id'],
        $sourcePeriod['teacher_id'],
        $sourcePeriod['is_double_period'],
        $targetPeriod['id']
    ]);

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
