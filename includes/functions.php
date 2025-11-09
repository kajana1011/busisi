<?php
/**
 * Core Functions for Busisi Timetable Generator
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login admin
 */
function loginAdmin($username, $password) {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        // Update last login
        $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$admin['id']]);

        return true;
    }

    return false;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Get school setting
 */
function getSetting($key, $default = '') {
    $db = getDB();

    $stmt = $db->prepare("SELECT setting_value FROM school_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();

    return $result ? $result['setting_value'] : $default;
}

/**
 * Update school setting
 */
function updateSetting($key, $value) {
    $db = getDB();

    $stmt = $db->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Display alert message
 */
function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear alert
 */
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

/**
 * Get all forms
 */
function getAllForms() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM forms ORDER BY display_order, name");
    return $stmt->fetchAll();
}

/**
 * Get form by ID
 */
function getFormById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create form
 */
function createForm($name, $description = '', $display_order = 0) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO forms (name, description, display_order) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $description, $display_order]);
}

/**
 * Update form
 */
function updateForm($id, $name, $description = '', $display_order = 0) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE forms SET name = ?, description = ?, display_order = ? WHERE id = ?");
    return $stmt->execute([$name, $description, $display_order, $id]);
}

/**
 * Delete form
 */
function deleteForm($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM forms WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all streams
 */
function getAllStreams() {
    $db = getDB();
    $stmt = $db->query("SELECT s.*, f.name as form_name
                       FROM streams s
                       JOIN forms f ON s.form_id = f.id
                       ORDER BY f.display_order, f.name, s.name");
    return $stmt->fetchAll();
}

/**
 * Get streams by form
 */
function getStreamsByForm($formId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM streams WHERE form_id = ? ORDER BY name");
    $stmt->execute([$formId]);
    return $stmt->fetchAll();
}

/**
 * Get stream by ID
 */
function getStreamById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, f.name as form_name
                         FROM streams s
                         JOIN forms f ON s.form_id = f.id
                         WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create stream
 */
function createStream($formId, $name, $description = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO streams (form_id, name, description) VALUES (?, ?, ?)");
    return $stmt->execute([$formId, $name, $description]);
}

/**
 * Update stream
 */
function updateStream($id, $formId, $name, $description = '') {
    $db = getDB();
    $stmt = $db->prepare("UPDATE streams SET form_id = ?, name = ?, description = ? WHERE id = ?");
    return $stmt->execute([$formId, $name, $description, $id]);
}

/**
 * Delete stream
 */
function deleteStream($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM streams WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all subjects
 */
function getAllSubjects() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM subjects ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get subject by ID
 */
function getSubjectById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create subject
 */
function createSubject($name, $code = '', $description = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO subjects (name, code, description) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $code, $description]);
}

/**
 * Update subject
 */
function updateSubject($id, $name, $code = '', $description = '') {
    $db = getDB();
    $stmt = $db->prepare("UPDATE subjects SET name = ?, code = ?, description = ? WHERE id = ?");
    return $stmt->execute([$name, $code, $description, $id]);
}

/**
 * Delete subject
 */
function deleteSubject($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all teachers
 */
function getAllTeachers() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM teachers ORDER BY last_name, first_name");
    return $stmt->fetchAll();
}

/**
 * Get teacher by ID
 */
function getTeacherById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get teacher full name
 */
function getTeacherName($id) {
    $teacher = getTeacherById($id);
    return $teacher ? $teacher['first_name'] . ' ' . $teacher['last_name'] : 'Unknown';
}

/**
 * Get teacher assignments with subjects and streams
 */
function getTeacherAssignments($teacherId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT sa.*,
                         s.name as stream_name, f.name as form_name,
                         sub.name as subject_name, sub.code as subject_code
                         FROM subject_assignments sa
                         JOIN streams s ON sa.stream_id = s.id
                         JOIN forms f ON s.form_id = f.id
                         JOIN subjects sub ON sa.subject_id = sub.id
                         WHERE sa.teacher_id = ?
                         ORDER BY f.display_order, s.name, sub.name");
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}

/**
 * Create teacher
 */
function createTeacher($firstName, $lastName, $email = '', $phone = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO teachers (first_name, last_name, email, phone)
                         VALUES (?, ?, ?, ?)");
    return $stmt->execute([$firstName, $lastName, $email, $phone]);
}

/**
 * Update teacher
 */
function updateTeacher($id, $firstName, $lastName, $email = '', $phone = '') {
    $db = getDB();
    $stmt = $db->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?
                         WHERE id = ?");
    return $stmt->execute([$firstName, $lastName, $email, $phone, $id]);
}

/**
 * Delete teacher
 */
function deleteTeacher($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all subject assignments
 */
function getAllAssignments() {
    $db = getDB();
    $stmt = $db->query("SELECT sa.*,
                       s.name as stream_name, f.name as form_name,
                       sub.name as subject_name, sub.code as subject_code,
                       CONCAT(t.first_name, ' ', t.last_name) as teacher_name
                       FROM subject_assignments sa
                       JOIN streams s ON sa.stream_id = s.id
                       JOIN forms f ON s.form_id = f.id
                       JOIN subjects sub ON sa.subject_id = sub.id
                       JOIN teachers t ON sa.teacher_id = t.id
                       ORDER BY f.display_order, s.name, sub.name");
    return $stmt->fetchAll();
}

/**
 * Get assignments by stream
 */
function getAssignmentsByStream($streamId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT sa.*,
                         sub.name as subject_name, sub.code as subject_code,
                         CONCAT(t.first_name, ' ', t.last_name) as teacher_name
                         FROM subject_assignments sa
                         JOIN subjects sub ON sa.subject_id = sub.id
                         JOIN teachers t ON sa.teacher_id = t.id
                         WHERE sa.stream_id = ?
                         ORDER BY sub.name");
    $stmt->execute([$streamId]);
    return $stmt->fetchAll();
}

/**
 * Create subject assignment
 */
function createAssignment($streamId, $subjectId, $teacherId, $periodsPerWeek) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO subject_assignments (stream_id, subject_id, teacher_id, periods_per_week)
                         VALUES (?, ?, ?, ?)");
    return $stmt->execute([$streamId, $subjectId, $teacherId, $periodsPerWeek]);
}

/**
 * Update subject assignment
 */
function updateAssignment($id, $streamId, $subjectId, $teacherId, $periodsPerWeek) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE subject_assignments
                         SET stream_id = ?, subject_id = ?, teacher_id = ?, periods_per_week = ?
                         WHERE id = ?");
    return $stmt->execute([$streamId, $subjectId, $teacherId, $periodsPerWeek, $id]);
}

/**
 * Delete subject assignment
 */
function deleteAssignment($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM subject_assignments WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get assignment by ID
 */
function getAssignmentById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT sa.*,
                         s.name as stream_name, f.name as form_name,
                         sub.name as subject_name, sub.code as subject_code,
                         CONCAT(t.first_name, ' ', t.last_name) as teacher_name
                         FROM subject_assignments sa
                         JOIN streams s ON sa.stream_id = s.id
                         JOIN forms f ON s.form_id = f.id
                         JOIN subjects sub ON sa.subject_id = sub.id
                         JOIN teachers t ON sa.teacher_id = t.id
                         WHERE sa.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get all special periods
 */
function getAllSpecialPeriods() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM special_periods ORDER BY day_of_week, start_period");
    return $stmt->fetchAll();
}

/**
 * Create special period
 */
function createSpecialPeriod($name, $dayOfWeek, $startPeriod, $endPeriod) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO special_periods (name, day_of_week, start_period, end_period)
                         VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $dayOfWeek, $startPeriod, $endPeriod]);
}

/**
 * Delete special period
 */
function deleteSpecialPeriod($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM special_periods WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all break periods
 */
function getAllBreakPeriods() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM break_periods ORDER BY period_number");
    return $stmt->fetchAll();
}

/**
 * Create break period
 */
function createBreakPeriod($name, $periodNumber, $duration) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO break_periods (name, period_number, duration_minutes)
                         VALUES (?, ?, ?)");
    return $stmt->execute([$name, $periodNumber, $duration]);
}

/**
 * Delete break period
 */
function deleteBreakPeriod($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM break_periods WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get day name
 */
function getDayName($dayNumber) {
    $days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    return $days[$dayNumber] ?? 'Unknown';
}

/**
 * Format time
 */
function formatTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Get assigned streams for a subject (formatted as "1A,3B")
 */
function getAssignedStreamsForSubject($subjectId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT DISTINCT f.name as form_name, s.name as stream_name
                         FROM subject_assignments sa
                         JOIN streams s ON sa.stream_id = s.id
                         JOIN forms f ON s.form_id = f.id
                         WHERE sa.subject_id = ?
                         ORDER BY f.display_order, s.name");
    $stmt->execute([$subjectId]);
    $assignments = $stmt->fetchAll();

    $streams = [];
    foreach ($assignments as $assignment) {
        // Extract number from form_name (e.g., "Form 1" -> "1")
        $formNumber = preg_replace('/[^0-9]/', '', $assignment['form_name']);
        $streams[] = $formNumber . $assignment['stream_name'];
    }

    return implode(', ', $streams);
}

/**
 * Check if setup is completed
 */
function isSetupCompleted() {
    return getSetting('setup_completed', '0') === '1';
}
