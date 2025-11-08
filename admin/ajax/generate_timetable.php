<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/timetable_generator.php';

requireLogin();

header('Content-Type: application/json');

try {
    $generator = new TimetableGenerator();
    $generator->generate();

    echo json_encode([
        'success' => true,
        'message' => 'Timetables generated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
