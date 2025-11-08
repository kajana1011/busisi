<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$streamId = isset($_GET['stream_id']) ? intval($_GET['stream_id']) : 0;

if (!$streamId) {
    showAlert('Invalid stream ID', 'danger');
    header('Location: view.php');
    exit;
}

// Get stream info
$stream = getStreamById($streamId);
if (!$stream) {
    showAlert('Stream not found', 'danger');
    header('Location: view.php');
    exit;
}

$db = getDB();

// Get timetable
$stmt = $db->prepare("SELECT t.*,
                     sub.name as subject_name, sub.code as subject_code,
                     CONCAT(te.first_name, ' ', te.last_name) as teacher_name,
                     sp.name as special_name
                     FROM timetables t
                     LEFT JOIN subjects sub ON t.subject_id = sub.id
                     LEFT JOIN teachers te ON t.teacher_id = te.id
                     LEFT JOIN special_periods sp ON t.special_period_id = sp.id
                     WHERE t.stream_id = ?
                     ORDER BY t.day_of_week, t.period_number");
$stmt->execute([$streamId]);
$results = $stmt->fetchAll();

$timetable = [];
foreach ($results as $row) {
    $timetable[$row['day_of_week']][$row['period_number']] = $row;
}

// Get settings
$schoolDays = intval(getSetting('school_days', 5));
$periodsPerDay = intval(getSetting('periods_per_day', 8));
$schoolName = getSetting('school_name', 'Busisi Secondary School');

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="timetable_' . $stream['form_name'] . '_' . $stream['name'] . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Write header
fputcsv($output, [$schoolName]);
fputcsv($output, ['Timetable for ' . $stream['form_name'] . ' - ' . $stream['name']]);
fputcsv($output, ['Academic Year: ' . getSetting('academic_year', date('Y'))]);
fputcsv($output, []); // Empty line

// Write table header
$header = ['Period'];
for ($day = 1; $day <= $schoolDays; $day++) {
    $header[] = getDayName($day);
}
fputcsv($output, $header);

// Write timetable rows
for ($period = 1; $period <= $periodsPerDay; $period++) {
    $row = [$period];

    for ($day = 1; $day <= $schoolDays; $day++) {
        $slot = $timetable[$day][$period] ?? null;

        if (!$slot) {
            $row[] = 'Free';
        } elseif ($slot['is_break']) {
            $row[] = 'BREAK';
        } elseif ($slot['is_special']) {
            $row[] = $slot['special_name'] ?? 'SPECIAL';
        } elseif ($slot['subject_id']) {
            $cellContent = $slot['subject_name'];
            if ($slot['is_double_period']) {
                $cellContent .= ' (Double)';
            }
            $cellContent .= ' - ' . $slot['teacher_name'];
            $row[] = $cellContent;
        } else {
            $row[] = 'Free';
        }
    }

    fputcsv($output, $row);
}

fclose($output);
exit;
