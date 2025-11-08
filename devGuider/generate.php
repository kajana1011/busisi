<?php
session_start();
// require_once '../database/config.php';
require_once '../helpers/functions.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$school_id = $_SESSION['school_id'];
$generation_message = '';
$generation_success = false;

// Get school info
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch(PDO::FETCH_ASSOC);

// Get school settings if set
$settings = getSchoolSettings($school_id);

// Get forms and streams
$allStreams = getAllStreams($school_id);
$streamsIDs = getStreamIDs($school_id);

// Get subjects
$subjects = getAllSubjects($school_id);

// Get teachers
$stmt = $pdo->prepare("SELECT id, name, email FROM teachers WHERE school_id = ? ORDER BY name");
$stmt->execute([$school_id]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subject assignments
if (count($streamsIDs) > 0) {
    $placeholders = implode(',', array_fill(0, count($streamsIDs), '?'));
    $stmt = $pdo->prepare("
        SELECT sa.stream_id, sa.subject_id, sa.periods_per_week, sa.teacher_id,
               s.name AS subject_name, t.name AS teacher_name, st.name AS stream_name, f.name AS form_name
        FROM subject_assignments sa
        JOIN subjects s ON sa.subject_id = s.id
        JOIN teachers t ON sa.teacher_id = t.id
        JOIN streams st ON sa.stream_id = st.id
        JOIN forms f ON st.form_id = f.id
        WHERE sa.stream_id IN ($placeholders)
        ORDER BY f.name, st.name, s.name
    ");
    $stmt->execute($streamsIDs);
} else {
    $subject_assignments = [];
}
$subject_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get special sessions
$special_sessions = getActiveSpecialSessions($school_id);

// Handle assignment management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_assignment'])) {
        $stream_id = $_POST['stream_id'];
        $subject_id = $_POST['subject_id'];
        $teacher_id = $_POST['teacher_id'];
        $periods_per_week = $_POST['periods_per_week'];

        $stmt = $pdo->prepare("INSERT INTO subject_assignments (stream_id, subject_id, teacher_id, periods_per_week) VALUES (?, ?, ?, ?)");
        $stmt->execute([$stream_id, $subject_id, $teacher_id, $periods_per_week]);
        header("Location: generate_new.php");
        exit;
    } elseif (isset($_POST['edit_assignment'])) {
        $assignment_id = $_POST['edit_assignment_id'];
        list($stream_id, $subject_id) = explode('-', $assignment_id);
        $teacher_id = $_POST['teacher_id'];
        $periods_per_week = $_POST['periods_per_week'];

        $stmt = $pdo->prepare("UPDATE subject_assignments SET teacher_id = ?, periods_per_week = ? WHERE stream_id = ? AND subject_id = ?");
        $stmt->execute([$teacher_id, $periods_per_week, $stream_id, $subject_id]);
        header("Location: generate_new.php");
        exit;
    } elseif (isset($_POST['delete_assignment'])) {
        $assignment_id = $_POST['delete_assignment'];
        list($stream_id, $subject_id) = explode('-', $assignment_id);

        $stmt = $pdo->prepare("DELETE FROM subject_assignments WHERE stream_id = ? AND subject_id = ?");
        $stmt->execute([$stream_id, $subject_id]);
        header("Location: generate_new.php");
        exit;
    }
}

// Handle generation if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    // Include the generation logic here
    // This is the same as the original script, but we'll capture output

    ob_start(); // Start output buffering

    // Helper function to get consecutive count before a period
    function getConsecutiveBefore($timetable, $stream_id, $day_name, $period, $subject_id) {
        $count = 0;
        for ($prev = $period - 1; $prev >= 1; --$prev) {
            if (isset($timetable[$stream_id][$day_name][$prev]) &&
                $timetable[$stream_id][$day_name][$prev]['type'] === 'subject' &&
                $timetable[$stream_id][$day_name][$prev]['subject_id'] === $subject_id) {
                ++$count;
            } else {
                break;
            }
        }
        return $count;
    }

    /**
     * Ensure timetable slot exists and is an array so $slot['type'] etc. are safe.
     */
    function ensureSlotArray(&$timetable, $stream_id, $day_name, $p) {
        if (!isset($timetable[$stream_id]) || !is_array($timetable[$stream_id])) {
            $timetable[$stream_id] = [];
        }
        if (!isset($timetable[$stream_id][$day_name]) || !is_array($timetable[$stream_id][$day_name])) {
            $timetable[$stream_id][$day_name] = [];
        }
        if (!isset($timetable[$stream_id][$day_name][$p]) || !is_array($timetable[$stream_id][$day_name][$p])) {
            $timetable[$stream_id][$day_name][$p] = ['type' => 'free'];
        }
    }

    // Get school settings
    $school_days = $settings['school_days'];
    $period_duration = $settings['period_duration'];

    $break_periods_str = $settings['break_periods'];
    $break_periods = array_map('intval', explode(',', $break_periods_str));
    $first_break = $break_periods[0];
    $second_break = $break_periods[1];

    $break_durations_str = $settings['break_durations'];
    $break_durations = array_map('intval', explode(',', $break_durations_str));
    $first_break_duration = $break_durations[0];
    $second_break_duration = $break_durations[1];

    $start_time_str = $settings['start_time'];
    $start = new DateTime('1970-01-01 ' . $start_time_str);

    $period_times = [];
    for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
        if ($p == $first_break) {
            $duration = $first_break_duration;
        } elseif ($p == $second_break) {
            $duration = $second_break_duration;
        } else {
            $duration = $period_duration;
        }

        $end = clone $start;
        $end->modify("+{$duration} minutes");
        $period_times[$p] = $start->format('H:i') . ' - ' . $end->format('H:i');
        $start = $end;
    }

    // Special sessions mapping
    $special_map = [];
    foreach ($special_sessions as $session) {
        $day = $session['day_of_week'];
        foreach ($period_times as $p => $timeRange) {
            [$start, $end] = explode(' - ', $timeRange);
            $period_start = new DateTime($start);
            $period_end = new DateTime($end);
            $session_start = new DateTime($session['start_time']);
            $session_end = new DateTime($session['end_time']);

            if ($session_start < $period_end && $session_end > $period_start) {
                $special_map[$day][$p] = $session;
            }
        }
    }

    $day_names = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];

    // Generation logic
    $timetable = [];
    $teacher_schedule = [];
    $subject_remaining = [];

    foreach ($allStreams as $stream) {
        $stream_id = $stream['id'];
        $timetable[$stream_id] = [];
        foreach ($day_names as $day_num => $day_name) {
            $timetable[$stream_id][$day_name] = [];
            for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
                $timetable[$stream_id][$day_name][$p] = ['type' => 'free', 'subject_id' => null, 'teacher_id' => null];
            }
        }
    }

    // Fetch subject assignments
    $placeholders = implode(',', array_fill(0, count($streamsIDs), '?'));
    $stmt = $pdo->prepare("
        SELECT sa.stream_id, sa.subject_id, sa.periods_per_week, sa.teacher_id, s.name AS subject_name
        FROM subject_assignments sa
        JOIN subjects s ON sa.subject_id = s.id
        WHERE sa.stream_id IN ($placeholders)
    ");
    $stmt->execute($streamsIDs);
    $subject_assignments_gen = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subject_assignments_gen as $sa) {
        $subject_remaining[$sa['stream_id']][$sa['subject_id']] = $sa['periods_per_week'];
    }

    // Pre-fill breaks
    foreach ($allStreams as $stream) {
        $stream_id = $stream['id'];
        foreach ($day_names as $day_num => $day_name) {
            foreach ($break_periods as $break_p) {
                $timetable[$stream_id][$day_name][$break_p]['type'] = 'break';
            }
        }
    }

    // Pre-fill special sessions
    foreach ($special_map as $day_num => $periods) {
        $day_name = $day_names[$day_num];
        foreach ($periods as $p => $session) {
            foreach ($allStreams as $stream) {
                $stream_id = $stream['id'];
                $timetable[$stream_id][$day_name][$p]['type'] = 'special';
                $timetable[$stream_id][$day_name][$p]['session'] = $session;
                if (isset($session['teacher_id'])) {
                    $teacher_schedule[$session['teacher_id']][$day_name][$p] = true;
                }
            }
        }
    }

    // Assign subjects with enhanced logic
    $subject_day_count = [];
    foreach ($allStreams as $stream) {
        $stream_id = $stream['id'];
        foreach ($day_names as $day_num => $day_name) {
            foreach ($subject_assignments_gen as $sa) {
                if ($sa['stream_id'] == $stream_id) {
                    $subject_day_count[$stream_id][$day_name][$sa['subject_id']] = 0;
                }
            }
        }
    }

    foreach ($allStreams as $stream) {
        $stream_id = $stream['id'];
        $stream_subjects_list = array_filter($subject_assignments_gen, fn($sa) => $sa['stream_id'] == $stream_id);

        foreach ($day_names as $day_num => $day_name) {
            // Sort subjects by remaining periods descending to prioritize subjects with more periods left
            usort($stream_subjects_list, function($a, $b) use ($subject_remaining, $stream_id) {
                return $subject_remaining[$stream_id][$b['subject_id']] <=> $subject_remaining[$stream_id][$a['subject_id']];
            });
            // Identify sections between breaks
            $sections = [];
            $current_section = [];
            for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
                if ($timetable[$stream_id][$day_name][$p]['type'] == 'free') {
                    $current_section[] = $p;
                } else {
                    if (!empty($current_section)) {
                        $sections[] = $current_section;
                        $current_section = [];
                    }
                }
            }
            if (!empty($current_section)) {
                $sections[] = $current_section;
            }

            // Assign to each section
            foreach ($sections as $section) {
                $num_periods = count($section);
                if ($num_periods % 2 == 0) {
                    // Even: assign double periods of different subjects, prevent more than 2 consecutives
                    for ($i = 0; $i < $num_periods; $i += 2) {
                        $p1 = $section[$i];
                        $p2 = $section[$i+1];
                        $assigned = false;
                        foreach ($stream_subjects_list as $subj) {
                            $subj_id = $subj['subject_id'];
                            $teacher_id = $subj['teacher_id'];
                            if ($subject_remaining[$stream_id][$subj_id] >= 2) {
                                $teacher_free1 = !isset($teacher_schedule[$teacher_id][$day_name][$p1]) || !$teacher_schedule[$teacher_id][$day_name][$p1];
                                $teacher_free2 = !isset($teacher_schedule[$teacher_id][$day_name][$p2]) || !$teacher_schedule[$teacher_id][$day_name][$p2];
                                $consec_p1 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p1, $subj_id);
                                $consec_p2 = $consec_p1 + 1;
                                if ($teacher_free1 && $teacher_free2 && $consec_p1 < 2 && $consec_p2 < 2) {
                                    $timetable[$stream_id][$day_name][$p1]['type'] = 'subject';
                                    $timetable[$stream_id][$day_name][$p1]['subject_id'] = $subj_id;
                                    $timetable[$stream_id][$day_name][$p1]['teacher_id'] = $teacher_id;
                                    $timetable[$stream_id][$day_name][$p2]['type'] = 'subject';
                                    $timetable[$stream_id][$day_name][$p2]['subject_id'] = $subj_id;
                                    $timetable[$stream_id][$day_name][$p2]['teacher_id'] = $teacher_id;
                                    $teacher_schedule[$teacher_id][$day_name][$p1] = true;
                                    $teacher_schedule[$teacher_id][$day_name][$p2] = true;
                                    $subject_remaining[$stream_id][$subj_id] -= 2;
                                    $subject_day_count[$stream_id][$day_name][$subj_id] += 2;
                                    $assigned = true;
                                    break;
                                }
                            }
                        }
                        if (!$assigned) {
                            // Fallback: assign singles if no double possible
                            foreach ($stream_subjects_list as $subj) {
                                $subj_id = $subj['subject_id'];
                                $teacher_id = $subj['teacher_id'];
                                if ($subject_remaining[$stream_id][$subj_id] > 0) {
                                    $teacher_free1 = !isset($teacher_schedule[$teacher_id][$day_name][$p1]) || !$teacher_schedule[$teacher_id][$day_name][$p1];
                                    $consec_p1 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p1, $subj_id);
                                    if ($teacher_free1 && $consec_p1 < 2) {
                                        $timetable[$stream_id][$day_name][$p1]['type'] = 'subject';
                                        $timetable[$stream_id][$day_name][$p1]['subject_id'] = $subj_id;
                                        $timetable[$stream_id][$day_name][$p1]['teacher_id'] = $teacher_id;
                                        $teacher_schedule[$teacher_id][$day_name][$p1] = true;
                                        $subject_remaining[$stream_id][$subj_id] -= 1;
                                        $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                        break;
                                    }
                                }
                            }
                            foreach ($stream_subjects_list as $subj) {
                                $subj_id = $subj['subject_id'];
                                $teacher_id = $subj['teacher_id'];
                                if ($subject_remaining[$stream_id][$subj_id] > 0) {
                                    $teacher_free2 = !isset($teacher_schedule[$teacher_id][$day_name][$p2]) || !$teacher_schedule[$teacher_id][$day_name][$p2];
                                    $consec_p2 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p2, $subj_id);
                                    if ($teacher_free2 && $consec_p2 < 2) {
                                        $timetable[$stream_id][$day_name][$p2]['type'] = 'subject';
                                        $timetable[$stream_id][$day_name][$p2]['subject_id'] = $subj_id;
                                        $timetable[$stream_id][$day_name][$p2]['teacher_id'] = $teacher_id;
                                        $teacher_schedule[$teacher_id][$day_name][$p2] = true;
                                        $subject_remaining[$stream_id][$subj_id] -= 1;
                                        $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // Odd: assign doubles and leave one slot
                    $leftover = null;
                    for ($i = 0; $i < $num_periods - 1; $i += 2) {
                        $p1 = $section[$i];
                        $p2 = $section[$i+1];
                        $assigned = false;
                        foreach ($stream_subjects_list as $subj) {
                            $subj_id = $subj['subject_id'];
                            $teacher_id = $subj['teacher_id'];
                            if ($subject_remaining[$stream_id][$subj_id] >= 2) {
                                $teacher_free1 = !isset($teacher_schedule[$teacher_id][$day_name][$p1]) || !$teacher_schedule[$teacher_id][$day_name][$p1];
                                $teacher_free2 = !isset($teacher_schedule[$teacher_id][$day_name][$p2]) || !$teacher_schedule[$teacher_id][$day_name][$p2];
                                $consec_p1 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p1, $subj_id);
                                $consec_p2 = $consec_p1 + 1;
                                if ($teacher_free1 && $teacher_free2 && $consec_p1 < 2 && $consec_p2 < 2) {
                                    $timetable[$stream_id][$day_name][$p1]['type'] = 'subject';
                                    $timetable[$stream_id][$day_name][$p1]['subject_id'] = $subj_id;
                                    $timetable[$stream_id][$day_name][$p1]['teacher_id'] = $teacher_id;
                                    $timetable[$stream_id][$day_name][$p2]['type'] = 'subject';
                                    $timetable[$stream_id][$day_name][$p2]['subject_id'] = $subj_id;
                                    $timetable[$stream_id][$day_name][$p2]['teacher_id'] = $teacher_id;
                                    $teacher_schedule[$teacher_id][$day_name][$p1] = true;
                                    $teacher_schedule[$teacher_id][$day_name][$p2] = true;
                                    $subject_remaining[$stream_id][$subj_id] -= 2;
                                    $subject_day_count[$stream_id][$day_name][$subj_id] += 2;
                                    $assigned = true;
                                    break;
                                }
                            }
                        }
                        if (!$assigned) {
                            // Fallback singles
                            foreach ($stream_subjects_list as $subj) {
                                $subj_id = $subj['subject_id'];
                                $teacher_id = $subj['teacher_id'];
                                if ($subject_remaining[$stream_id][$subj_id] > 0) {
                                    $teacher_free1 = !isset($teacher_schedule[$teacher_id][$day_name][$p1]) || !$teacher_schedule[$teacher_id][$day_name][$p1];
                                    $consec_p1 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p1, $subj_id);
                                    if ($teacher_free1 && $consec_p1 < 2) {
                                        $timetable[$stream_id][$day_name][$p1]['type'] = 'subject';
                                        $timetable[$stream_id][$day_name][$p1]['subject_id'] = $subj_id;
                                        $timetable[$stream_id][$day_name][$p1]['teacher_id'] = $teacher_id;
                                        $teacher_schedule[$teacher_id][$day_name][$p1] = true;
                                        $subject_remaining[$stream_id][$subj_id] -= 1;
                                        $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                        break;
                                    }
                                }
                            }
                            foreach ($stream_subjects_list as $subj) {
                                $subj_id = $subj['subject_id'];
                                $teacher_id = $subj['teacher_id'];
                                if ($subject_remaining[$stream_id][$subj_id] > 0) {
                                    $teacher_free2 = !isset($teacher_schedule[$teacher_id][$day_name][$p2]) || !$teacher_schedule[$teacher_id][$day_name][$p2];
                                    $consec_p2 = getConsecutiveBefore($timetable, $stream_id, $day_name, $p2, $subj_id);
                                    if ($teacher_free2 && $consec_p2 < 2) {
                                        $timetable[$stream_id][$day_name][$p2]['type'] = 'subject';
                                        $timetable[$stream_id][$day_name][$p2]['subject_id'] = $subj_id;
                                        $timetable[$stream_id][$day_name][$p2]['teacher_id'] = $teacher_id;
                                        $teacher_schedule[$teacher_id][$day_name][$p2] = true;
                                        $subject_remaining[$stream_id][$subj_id] -= 1;
                                        $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    $leftover = $section[$num_periods - 1];
                    // Assign leftover to odd-parity subjects
                    foreach ($stream_subjects_list as $subj) {
                        $subj_id = $subj['subject_id'];
                        $teacher_id = $subj['teacher_id'];
                        if ($subject_remaining[$stream_id][$subj_id] % 2 == 1 && $subject_remaining[$stream_id][$subj_id] > 0) {
                            $teacher_free = !isset($teacher_schedule[$teacher_id][$day_name][$leftover]) || !$teacher_schedule[$teacher_id][$day_name][$leftover];
                            $consec_leftover = getConsecutiveBefore($timetable, $stream_id, $day_name, $leftover, $subj_id);
                            if ($teacher_free && $consec_leftover < 2) {
                                $timetable[$stream_id][$day_name][$leftover]['type'] = 'subject';
                                $timetable[$stream_id][$day_name][$leftover]['subject_id'] = $subj_id;
                                $timetable[$stream_id][$day_name][$leftover]['teacher_id'] = $teacher_id;
                                $teacher_schedule[$teacher_id][$day_name][$leftover] = true;
                                $subject_remaining[$stream_id][$subj_id] -= 1;
                                $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                break;
                            }
                        }
                    }
                    // If no odd-parity subject, assign any remaining
                    if ($timetable[$stream_id][$day_name][$leftover]['type'] == 'free') {
                        foreach ($stream_subjects_list as $subj) {
                            $subj_id = $subj['subject_id'];
                            $teacher_id = $subj['teacher_id'];
                            if ($subject_remaining[$stream_id][$subj_id] > 0) {
                                $teacher_free = !isset($teacher_schedule[$teacher_id][$day_name][$leftover]) || !$teacher_schedule[$teacher_id][$day_name][$leftover];
                                $consec_leftover = getConsecutiveBefore($timetable, $stream_id, $day_name, $leftover, $subj_id);
                                if ($teacher_free && $consec_leftover < 2) {
                                    $timetable[$stream_id][$day_name][$leftover]['type'] = 'subject';
                                    $timetable[$stream_id][$day_name][$leftover]['subject_id'] = $subj_id;
                                    $timetable[$stream_id][$day_name][$leftover]['teacher_id'] = $teacher_id;
                                    $teacher_schedule[$teacher_id][$day_name][$leftover] = true;
                                    $subject_remaining[$stream_id][$subj_id] -= 1;
                                    $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Validation
    $valid = true;
    foreach ($subject_remaining as $stream_id => $subjects) {
        foreach ($subjects as $subj_id => $remaining) {
            if ($remaining > 0) {
                $valid = false;
            }
        }
    }

    // Try a greedy second-pass: fill any remaining subject periods into any free slots
    if (!$valid) {
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($allStreams as $stream) {
                $stream_id = $stream['id'];
                foreach ($day_names as $day_num => $day_name) {
                    for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
                        if ($timetable[$stream_id][$day_name][$p]['type'] !== 'free') {
                            continue;
                        }
                        // Try to assign any subject with remaining > 0 whose teacher is free and consecutive constraint ok
                        foreach ($subject_assignments_gen as $sa) {
                            if ($sa['stream_id'] != $stream_id) continue;
                            $subj_id = $sa['subject_id'];
                            if (empty($subject_remaining[$stream_id][$subj_id]) || $subject_remaining[$stream_id][$subj_id] <= 0) continue;
                            $teacher_id = $sa['teacher_id'];
                            $teacher_free = !isset($teacher_schedule[$teacher_id][$day_name][$p]) || !$teacher_schedule[$teacher_id][$day_name][$p];
                            $consec = getConsecutiveBefore($timetable, $stream_id, $day_name, $p, $subj_id);
                            if ($teacher_free && $consec < 2) {
                                $timetable[$stream_id][$day_name][$p]['type'] = 'subject';
                                $timetable[$stream_id][$day_name][$p]['subject_id'] = $subj_id;
                                $timetable[$stream_id][$day_name][$p]['teacher_id'] = $teacher_id;
                                $teacher_schedule[$teacher_id][$day_name][$p] = true;
                                $subject_remaining[$stream_id][$subj_id] -= 1;
                                $subject_day_count[$stream_id][$day_name][$subj_id] += 1;
                                $changed = true;
                                break;
                            }
                        }
                        if ($changed) break;
                    }
                    if ($changed) break;
                }
                if ($changed) break;
            }
        } // end greedy loop

        // Re-check validity
        $valid = true;
        foreach ($subject_remaining as $stream_id => $subjects) {
            foreach ($subjects as $subj_id => $remaining) {
                if ($remaining > 0) {
                    $valid = false;
                }
            }
        }
    }

    if ($valid) {
        // Save to database (unchanged)
        $pdo->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($allStreams), '?'));
            $stream_ids = array_column($allStreams, 'id');
            $stmt = $pdo->prepare("DELETE FROM timetables WHERE stream_id IN ($placeholders)");
            $stmt->execute($stream_ids);

            foreach ($timetable as $stream_id => $days_data) {
                foreach ($days_data as $day_name => $periods) {
                    $day_num = array_search($day_name, $day_names);
                    for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
                        ensureSlotArray($timetable, $stream_id, $day_name, $p);
                        $slot = $timetable[$stream_id][$day_name][$p];
                        if ($slot['type'] == 'subject') {
                            $is_double = 0;
                            if ($p < $settings['periods_per_day'] && isset($timetable[$stream_id][$day_name][$p+1]) && $timetable[$stream_id][$day_name][$p+1]['type'] == 'subject' && $timetable[$stream_id][$day_name][$p+1]['subject_id'] == $slot['subject_id']) {
                                $is_double = 1;
                            }
                            $stmt = $pdo->prepare("INSERT INTO timetables (stream_id, day_of_week, period_number, subject_id, teacher_id, is_break, is_double, special_session_id, is_special) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$stream_id, $day_num, $p, $slot['subject_id'], $slot['teacher_id'] ?? null, 0, $is_double, $slot['session']['id'] ?? null, !empty($slot['session']) ? 1 : 0]);
                        } elseif ($slot['type'] == 'break') {
                            $stmt = $pdo->prepare("INSERT INTO timetables (stream_id, day_of_week, period_number, is_break, is_double, special_session_id, is_special) VALUES (?, ?, ?, 1, 0, NULL, 0)");
                            $stmt->execute([$stream_id, $day_num, $p]);
                        } elseif ($slot['type'] == 'special') {
                            // Use the computed $day_num and the slot's session data
                            $sess = $slot['session'];
                            $sql = "INSERT INTO timetables
                                    (stream_id, day_of_week, period_number, is_break, is_double, subject_id, teacher_id, special_session_id, is_special)
                                    VALUES (:stream_id, :day_of_week, :period_number, :is_break, :is_double, :subject_id, :teacher_id, :special_session_id, :is_special)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':stream_id' => $stream_id,
                                ':day_of_week' => $day_num,
                                ':period_number' => $p,
                                ':is_break' => 0,
                                ':is_double' => 0,
                                ':subject_id' => null,
                                ':teacher_id' => $sess['teacher_id'] ?? null,
                                ':special_session_id' => $sess['id'] ?? null,
                                ':is_special' => 1
                            ]);
                        }
                    }
                }
            }
            $pdo->commit();
            $generation_success = true;
            $generation_message = "Timetable generated and saved successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $generation_message = "Error saving timetable: " . $e->getMessage();
        }
    } else {
        // Enhanced diagnostics: write detailed debug info to log and show concise message to user
        // Build lookup maps
        $subject_names = [];
        if (!empty($subjects) && is_array($subjects)) {
            foreach ($subjects as $s) {
                $subject_names[$s['id']] = $s['name'] ?? ('id:'.$s['id']);
            }
        }
        $stream_names = [];
        foreach ($allStreams as $st) {
            $stream_names[$st['id']] = $st['name'] ?? ('stream:'.$st['id']);
        }
        $teacher_names = [];
        foreach ($teachers as $t) {
            $teacher_names[$t['id']] = $t['name'] ?? ('teacher:'.$t['id']);
        }

        $log_lines = [];
        $log_lines[] = "Timetable generation diagnostics - " . date('c');
        $log_lines[] = "School ID: $school_id";
        $total_remaining = 0;

        $log_lines[] = "Remaining subject counts (per stream):";
        foreach ($subject_remaining as $stream_id => $subs) {
            $log_lines[] = "Stream {$stream_id} (" . ($stream_names[$stream_id] ?? 'unknown') . "):";
            foreach ($subs as $subj_id => $remaining) {
                if ($remaining > 0) {
                    $log_lines[] = sprintf("  - %s (id:%d): %d remaining", $subject_names[$subj_id] ?? "id:$subj_id", $subj_id, $remaining);
                    $total_remaining += $remaining;
                }
            }
        }
        $log_lines[] = "Total remaining periods: $total_remaining";

        // Detailed free slots listing
        $log_lines[] = "Free slots (stream => [day:period,...]) :";
        foreach ($allStreams as $stream) {
            $sid = $stream['id'];
            $free_list = [];
            foreach ($day_names as $day_num => $day_name) {
                for ($p = 1; $p <= $settings['periods_per_day']; $p++) {
                    $slot = $timetable[$sid][$day_name][$p] ?? null;
                    if (is_array($slot) && ($slot['type'] ?? 'free') === 'free') {
                        $free_list[] = "{$day_name}:{$p}";
                    }
                }
            }
            $stream_label = isset($stream_names[$sid]) ? $stream_names[$sid] : 'unknown';
            $log_lines[] = ' - ' . $sid . ' (' . $stream_label . '): ' . (empty($free_list) ? 'none' : implode(', ', $free_list));
        }

        // Teacher busy / conflicts summary with more detail
        $log_lines[] = "Teacher busy slots summary (id => count, sample busy positions):";
        foreach ($teachers as $t) {
            $tid = $t['id'];
            $count = 0;
            $positions = [];
            if (!empty($teacher_schedule[$tid]) && is_array($teacher_schedule[$tid])) {
                foreach ($teacher_schedule[$tid] as $dn => $periods) {
                    foreach ($periods as $pp => $v) {
                        $count++;
                        if (count($positions) < 10) {
                            $day_label = isset($day_names[$dn]) ? $day_names[$dn] : $dn;
                            $positions[] = $day_label . ':' . $pp;
                        }
                    }
                }
            }
            $log_lines[] = sprintf(" - %s (id:%d): %d busy slots. Sample: %s", $teacher_names[$tid] ?? "id:$tid", $tid, $count, empty($positions) ? 'none' : implode(';', $positions));
        }

        // Optional: dump subject_assignments_gen to help debug teacher-stream mapping
        $log_lines[] = "Subject assignments (stream_id => subject_id => teacher_id):";
        if (!empty($subject_assignments_gen)) {
            foreach ($subject_assignments_gen as $sa) {
                $log_lines[] = sprintf(" - stream:%d subject:%d teacher:%d", $sa['stream_id'], $sa['subject_id'], $sa['teacher_id']);
            }
        }

        $logfile = __DIR__ . '/generation_debug.log';
        file_put_contents($logfile, implode(PHP_EOL, $log_lines) . PHP_EOL . str_repeat('-',80) . PHP_EOL, FILE_APPEND | LOCK_EX);

        $generation_message = "Some subjects could not be fully assigned. See debug log: " . $logfile;
    }
    ob_end_clean(); // Discard the buffered output
}

include '../includes/header.php';
?>

    <!-- Main Content -->
    <div class="container my-5">
        <h1 class="mb-4">Generate Timetable</h1>

        <?php if ($generation_message): ?>
            <div class="alert alert-<?php echo $generation_success ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($generation_message); ?>
            </div>
        <?php endif; ?>

        <!-- School Settings Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-gear"></i> School Settings</h5>
            </div>
            <?php if(!$settings): ?>
                <div>School settings not set <a href="settings">here</a></div>
            <?php else: ?>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>School Days:</strong> <?php echo $settings['school_days']; ?> per week
                        </div>
                        <div class="col-md-3">
                            <strong>Periods per Day:</strong> <?php echo $settings['periods_per_day']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Period Duration:</strong> <?php echo $settings['period_duration']; ?> minutes
                        </div>
                        <div class="col-md-3">
                            <strong>Start Time:</strong> <?php echo $settings['start_time']; ?>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Break Periods:</strong> <?php echo $settings['break_periods']; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Break Durations:</strong> <?php echo $settings['break_durations']; ?> minutes
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Forms and Streams -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-diagram-3"></i> Forms and Streams</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th>Stream</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allStreams as $stream): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stream['fname']); ?></td>
                                    <td><?php echo htmlspecialchars($stream['sname']); ?></td>
                                    <td><?php echo $stream['capacity'] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subjects -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-book"></i> Subjects (<?php echo count($subjects); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($subjects == []): ?>
                        <p class="text-muted">No subjects configured.</p>
                    <?php else: ?>
                        <?php foreach ($subjects as $subject): ?>
                            <div class="col-md-3 mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($subject['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Teachers -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-person-badge"></i> Teachers (<?php echo count($teachers); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subject Assignments -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-link"></i> Subject Assignments (<?php echo count($subject_assignments); ?>)</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                    <i class="bi bi-plus-circle"></i> Add Assignment
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th>Stream</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Periods/Week</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subject_assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['form_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['stream_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                    <td><?php echo $assignment['periods_per_week']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-assignment"
                                                data-id="<?php echo $assignment['stream_id'] . '-' . $assignment['subject_id']; ?>"
                                                data-teacher="<?php echo $assignment['teacher_id']; ?>"
                                                data-periods="<?php echo $assignment['periods_per_week']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#editAssignmentModal">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="delete_assignment" value="<?php echo $assignment['stream_id'] . '-' . $assignment['subject_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this assignment?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Special Sessions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-calendar-event"></i> Special Sessions (<?php echo count($special_sessions); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($special_sessions)): ?>
                    <p class="text-muted">No special sessions configured.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Session Name</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($special_sessions as $session): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                        <td><?php echo ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$session['day_of_week'] - 1]; ?></td>
                                        <td><?php echo htmlspecialchars($session['start_time'] . ' - ' . $session['end_time']); ?></td>
                                        <td><?php echo htmlspecialchars($session['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="card">
            <div class="card-body text-center">
                <h5>Ready to Generate Timetable?</h5>
                <p class="text-muted">This will create a conflict-free timetable based on your current school configuration.</p>
                <form method="post" onsubmit="return confirm('Are you sure you want to generate the timetable? This will replace any existing timetable.');">
                    <button type="submit" name="generate" class="btn btn-success btn-lg">
                        <i class="bi bi-play-circle"></i> Generate Timetable
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <!-- <script src="../assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script> -->
<?php include 'assignment_modals.php'; ?>
<?php include '../includes/footer.php'; ?>
