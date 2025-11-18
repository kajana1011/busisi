<?php
/**
 * Timetable Generation Algorithm
 * Generates conflict-free timetables with support for:
 * - Even distribution of periods
 * - Single and double periods
 * - Special periods (sports, debate, religion)
 * - Break periods
 * - Teacher conflict avoidance
 */

class TimetableGenerator {
    private $db;
    private $schoolDays;
    private $periodsPerDay;
    private $breakPeriods = [];
    private $specialPeriods = [];
    private $streams = [];
    private $assignments = [];
    private $timetable = [];
    private $teacherSchedule = [];

    public function __construct() {
        $this->db = getDB();
        $this->loadSettings();
        $this->loadBreakPeriods();
        $this->loadSpecialPeriods();
    }

    /**
     * Load school settings
     */
    private function loadSettings() {
        $this->schoolDays = intval(getSetting('school_days', 5));
        $this->periodsPerDay = intval(getSetting('periods_per_day', 8));
    }

    /**
     * Load break periods
     */
    private function loadBreakPeriods() {
        $stmt = $this->db->query("SELECT * FROM break_periods");
        $this->breakPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Load special periods
     */
    private function loadSpecialPeriods() {
        $stmt = $this->db->query("SELECT * FROM special_periods WHERE is_active = 1");
        $this->specialPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate timetables for all streams
     */
    public function generate() {
        try {
            // Clear existing timetables
            $this->db->exec("DELETE FROM timetables");

            // Get all streams
            $stmt = $this->db->query("SELECT * FROM streams ORDER BY id");
            $this->streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($this->streams)) {
                throw new Exception("No streams found");
            }

            // Generate for each stream
            foreach ($this->streams as $stream) {
                $this->generateForStream($stream['id']);
            }

            // Log generation
            $adminId = $_SESSION['admin_id'] ?? null;
            if ($adminId !== null) {
                // Check if admin exists
                $checkStmt = $this->db->prepare("SELECT id FROM admins WHERE id = ?");
                $checkStmt->execute([$adminId]);
                if (!$checkStmt->fetch()) {
                    $adminId = null;
                }
            }
            $stmt = $this->db->prepare("INSERT INTO generation_history (generated_by, status, notes)
                                       VALUES (?, 'success', ?)");
            $stmt->execute([$adminId, 'Generated for ' . count($this->streams) . ' streams']);

            return true;
        } catch (Exception $e) {
            // Log error
            $adminId = $_SESSION['admin_id'] ?? null;
            if ($adminId !== null) {
                // Check if admin exists
                $checkStmt = $this->db->prepare("SELECT id FROM admins WHERE id = ?");
                $checkStmt->execute([$adminId]);
                if (!$checkStmt->fetch()) {
                    $adminId = null;
                }
            }
            $stmt = $this->db->prepare("INSERT INTO generation_history (generated_by, status, notes)
                                       VALUES (?, 'failed', ?)");
            $stmt->execute([$adminId, $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Generate timetable for a specific stream
     */
    private function generateForStream($streamId) {
        // Reset teacher schedule for this stream
        $this->teacherSchedule = [];

        // Initialize timetable grid
        $this->timetable = [];
        for ($day = 1; $day <= $this->schoolDays; $day++) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $this->timetable[$day][$period] = null;
            }
        }

        // Mark break periods
        foreach ($this->breakPeriods as $break) {
            for ($day = 1; $day <= $this->schoolDays; $day++) {
                if ($break['period_number'] <= $this->periodsPerDay) {
                    $this->timetable[$day][$break['period_number']] = [
                        'type' => 'break',
                        'name' => $break['name']
                    ];
                }
            }
        }

        // Mark special periods
        foreach ($this->specialPeriods as $special) {
            if ($special['day_of_week'] <= $this->schoolDays) {
                for ($p = $special['start_period']; $p <= $special['end_period']; $p++) {
                    if ($p <= $this->periodsPerDay) {
                        $this->timetable[$special['day_of_week']][$p] = [
                            'type' => 'special',
                            'name' => $special['name'],
                            'special_id' => $special['id']
                        ];
                    }
                }
            }
        }

        // Get assignments for this stream
        $stmt = $this->db->prepare("SELECT * FROM subject_assignments WHERE stream_id = ?");
        $stmt->execute([$streamId]);
        $this->assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare periods to assign
        $periodsToAssign = [];
        foreach ($this->assignments as $assignment) {
            $periodsCount = $assignment['periods_per_week'];

            // Calculate double and single periods
            $doublePeriods = floor($periodsCount / 2);
            $singlePeriods = $periodsCount % 2;

            // Add double periods first
            for ($i = 0; $i < $doublePeriods; $i++) {
                $periodsToAssign[] = [
                    'assignment' => $assignment,
                    'type' => 'double',
                    'is_first' => true
                ];
            }

            // Add single periods
            for ($i = 0; $i < $singlePeriods; $i++) {
                $periodsToAssign[] = [
                    'assignment' => $assignment,
                    'type' => 'single'
                ];
            }
        }

        // Shuffle for randomization
        shuffle($periodsToAssign);

        // Sort to prioritize double periods
        usort($periodsToAssign, function($a, $b) {
            if ($a['type'] === 'double' && $b['type'] !== 'double') return -1;
            if ($a['type'] !== 'double' && $b['type'] === 'double') return 1;
            return 0;
        });

        // Assign periods
        foreach ($periodsToAssign as $period) {
            $this->assignPeriod($streamId, $period);
        }

        // Save to database
        $this->saveTimetable($streamId);
    }

    /**
     * Assign a period to the timetable
     */
    private function assignPeriod($streamId, $periodData) {
        $assignment = $periodData['assignment'];
        $isDouble = $periodData['type'] === 'double';
        $maxAttempts = 100;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;

            // Random day and period
            $day = rand(1, $this->schoolDays);
            $period = rand(1, $this->periodsPerDay);

            // Check if slot is available
            if ($this->timetable[$day][$period] !== null) {
                continue;
            }

            // For double periods, check next slot too
            if ($isDouble) {
                if ($period >= $this->periodsPerDay) {
                    continue; // Can't fit double period at last slot
                }
                if ($this->timetable[$day][$period + 1] !== null) {
                    continue; // Next slot not available
                }
            }

            // Check teacher availability
            if ($this->isTeacherBusy($assignment['teacher_id'], $day, $period)) {
                continue;
            }

            if ($isDouble && $this->isTeacherBusy($assignment['teacher_id'], $day, $period + 1)) {
                continue;
            }

            // Check for even distribution (avoid too many periods on same day)
            if ($this->hasTooManyPeriodsOnDay($assignment['subject_id'], $day, $isDouble ? 2 : 1)) {
                continue;
            }

            // Assign the period(s)
            $this->timetable[$day][$period] = [
                'type' => 'class',
                'subject_id' => $assignment['subject_id'],
                'teacher_id' => $assignment['teacher_id'],
                'is_double' => $isDouble ? 1 : 0
            ];

            $this->markTeacherBusy($assignment['teacher_id'], $day, $period);

            if ($isDouble) {
                $this->timetable[$day][$period + 1] = [
                    'type' => 'class',
                    'subject_id' => $assignment['subject_id'],
                    'teacher_id' => $assignment['teacher_id'],
                    'is_double' => 0, // Second part of double
                    'is_continuation' => true
                ];
                $this->markTeacherBusy($assignment['teacher_id'], $day, $period + 1);
            }

            return true;
        }

        // Could not assign after max attempts
        error_log("Could not assign period for subject {$assignment['subject_id']} in stream $streamId");
        return false;
    }

    /**
     * Check if teacher is busy at this time
     */
    private function isTeacherBusy($teacherId, $day, $period) {
        $key = "{$day}_{$period}";
        return isset($this->teacherSchedule[$teacherId][$key]);
    }

    /**
     * Mark teacher as busy
     */
    private function markTeacherBusy($teacherId, $day, $period) {
        $key = "{$day}_{$period}";
        $this->teacherSchedule[$teacherId][$key] = true;
    }

    /**
     * Check if subject already has too many periods on this day
     */
    private function hasTooManyPeriodsOnDay($subjectId, $day, $addingCount) {
        $count = 0;
        for ($p = 1; $p <= $this->periodsPerDay; $p++) {
            $slot = $this->timetable[$day][$p];
            if ($slot !== null && isset($slot['subject_id']) && $slot['subject_id'] == $subjectId) {
                $count++;
            }
        }

        // Don't allow more than 2 periods of same subject in one day
        return ($count + $addingCount) > 2;
    }

    /**
     * Save timetable to database
     */
    private function saveTimetable($streamId) {
        $stmt = $this->db->prepare("INSERT INTO timetables
                                    (stream_id, day_of_week, period_number, subject_id, teacher_id,
                                     is_break, is_special, is_double_period, special_period_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        for ($day = 1; $day <= $this->schoolDays; $day++) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $slot = $this->timetable[$day][$period];

                if ($slot === null) {
                    // Empty slot - insert as free period
                    $stmt->execute([
                        $streamId, $day, $period, null, null, 0, 0, 0, null
                    ]);
                } elseif ($slot['type'] === 'break') {
                    // Break period
                    $stmt->execute([
                        $streamId, $day, $period, null, null, 1, 0, 0, null
                    ]);
                } elseif ($slot['type'] === 'special') {
                    // Special period
                    $stmt->execute([
                        $streamId, $day, $period, null, null, 0, 1, 0, $slot['special_id']
                    ]);
                } elseif ($slot['type'] === 'class') {
                    // Regular class
                    // Insert every slot (both first and second half of doubles)
                    // Only the FIRST half gets is_double_period = 1
                    $isDoubleFlag = (isset($slot['is_continuation']) && $slot['is_continuation']) ? 0 : $slot['is_double'];

                    $stmt->execute([
                        $streamId, $day, $period,
                        $slot['subject_id'],
                        $slot['teacher_id'],
                        0, 0,
                        $isDoubleFlag,
                        null
                    ]);
                }
            }
        }
    }
}
