<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$streamId = isset($_GET['stream_id']) ? intval($_GET['stream_id']) : 0;
$exportAllStreams = isset($_GET['all_streams']) && $_GET['all_streams'] === '1';

if (!$streamId && !$exportAllStreams) {
    showAlert('Invalid request', 'danger');
    header('Location: view.php');
    exit;
}

$db = getDB();

// Get settings
$schoolDays = intval(getSetting('school_days', 5));
$periodsPerDay = intval(getSetting('periods_per_day', 8));
$schoolName = getSetting('school_name', 'Busisi Secondary School');

// Handle different export formats
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

if ($exportAllStreams) {
    // Export entire timetable (All Streams)
    // Handle PDF export (requires DOMPDF), Excel (HTML table) or CSV fallback
    if ($format === 'pdf') {
        // Try to use Dompdf if installed via Composer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        if (class_exists('\Dompdf\Dompdf')) {
            // We'll render PDF later after building HTML
        } else {
            // PDF library not installed — fall back to CSV with a notice
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="timetable_all_streams.csv"');
        }
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="timetable_all_streams.xls"');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="timetable_all_streams.csv"');
    }

    // Get all streams and their timetables
    $stmt = $db->query("SELECT st.id, st.name, f.name as form_name FROM streams st JOIN forms f ON st.form_id = f.id ORDER BY st.id");
    $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all timetables
    $stmt = $db->prepare("SELECT t.*,
                         sub.name as subject_name, sub.code as subject_code,
                         CONCAT(te.first_name, ' ', te.last_name) as teacher_name,
                         sp.name as special_name
                         FROM timetables t
                         LEFT JOIN subjects sub ON t.subject_id = sub.id
                         LEFT JOIN teachers te ON t.teacher_id = te.id
                         LEFT JOIN special_periods sp ON t.special_period_id = sp.id
                         ORDER BY t.stream_id, t.day_of_week, t.period_number");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize by stream and day/period
    $allTimetables = [];
    foreach ($results as $row) {
        $allTimetables[$row['stream_id']][$row['day_of_week']][$row['period_number']] = $row;
    }

    if ($format === 'pdf' && class_exists('\Dompdf\Dompdf')) {
        // Build the same HTML as for Excel then render to PDF
        $html = "<html><head><meta charset='utf-8' /><style>table{border-collapse:collapse;}td,th{border:1px solid #999;padding:4px;font-size:12px;} .badge{background:#0dcaf0;padding:2px 4px;border-radius:4px;color:#000;font-size:10px;}</style></head><body>";
        $html .= "<h3>" . htmlspecialchars($schoolName) . "</h3>";
        $html .= "<p>Full School Timetable - Academic Year: " . htmlspecialchars(getSetting('academic_year', date('Y'))) . "</p>";
        $html .= '<table>';
        $html .= '<thead><tr><th></th><th></th>';
        for ($period = 1; $period <= $periodsPerDay; $period++) {
            $html .= '<th>Period ' . $period . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach (range(1, $schoolDays) as $day) {
            $isFirstStreamRow = true;
            foreach ($streams as $stream) {
                $html .= '<tr>';
                if ($isFirstStreamRow) {
                    $html .= '<td rowspan="' . count($streams) . '" style="font-weight:bold;vertical-align:middle;">' . htmlspecialchars(getDayName($day)) . '</td>';
                    $isFirstStreamRow = false;
                }
                $html .= '<td style="font-weight:bold;">' . htmlspecialchars($stream['form_name'] . ' ' . $stream['name']) . '</td>';

                $prevWasDouble = false;
                for ($p = 1; $p <= $periodsPerDay; $p++) {
                    $slot = $allTimetables[$stream['id']][$day][$p] ?? null;
                    if ($prevWasDouble) {
                        if ($slot && $slot['subject_id']) {
                            $html .= '<td>' . htmlspecialchars($slot['subject_name']) . ' <span class="badge">Double (2/2)</span><br>' . htmlspecialchars($slot['teacher_name']) . '</td>';
                        } else {
                            $html .= '<td>Free</td>';
                        }
                        $prevWasDouble = false;
                        continue;
                    }
                    if (!$slot) { $html .= '<td>Free</td>'; continue; }
                    if (!empty($slot['is_break'])) { $html .= '<td><strong>BREAK</strong></td>'; continue; }
                    if (!empty($slot['is_special'])) { $html .= '<td><strong>' . htmlspecialchars($slot['special_name'] ?? 'SPECIAL') . '</strong></td>'; continue; }
                    if (!empty($slot['subject_id'])) {
                        if (!empty($slot['is_double_period'])) { $html .= '<td>' . htmlspecialchars($slot['subject_name']) . ' <span class="badge">Double (1/2)</span><br>' . htmlspecialchars($slot['teacher_name']) . '</td>'; $prevWasDouble = true; }
                        else { $html .= '<td>' . htmlspecialchars($slot['subject_name']) . '<br>' . htmlspecialchars($slot['teacher_name']) . '</td>'; }
                        continue;
                    }
                    $html .= '<td>Free</td>';
                }

                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table></body></html>';

        // Render PDF with Dompdf
    require_once __DIR__ . '/../vendor/autoload.php';
    $dompdfClass = '\\Dompdf\\Dompdf';
    $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('timetable_all_streams.pdf', ['Attachment' => 1]);
        exit;
    } elseif ($format === 'excel') {
        // Build HTML table for Excel
        echo "<html><head><meta charset='utf-8' /><style>table{border-collapse:collapse;}td,th{border:1px solid #999;padding:4px;font-size:12px;} .badge{background:#0dcaf0;padding:2px 4px;border-radius:4px;color:#000;font-size:10px;}</style></head><body>";
        echo "<h3>" . htmlspecialchars($schoolName) . "</h3>";
        echo "<p>Full School Timetable - Academic Year: " . htmlspecialchars(getSetting('academic_year', date('Y'))) . "</p>";

        echo '<table>';
        // Header row (empty day + stream name + period headers)
        echo '<thead><tr><th></th><th></th>';
        for ($period = 1; $period <= $periodsPerDay; $period++) {
            echo '<th>Period ' . $period . '</th>';
        }
        echo '</tr></thead>';

        echo '<tbody>';
        // For each day, print rows for each stream with day label rowspan
        foreach (range(1, $schoolDays) as $day) {
            $isFirstStreamRow = true;
            foreach ($streams as $stream) {
                echo '<tr>';
                if ($isFirstStreamRow) {
                    echo '<td rowspan="' . count($streams) . '" style="font-weight:bold;vertical-align:middle;">' . htmlspecialchars(getDayName($day)) . '</td>';
                    $isFirstStreamRow = false;
                }
                echo '<td style="font-weight:bold;">' . htmlspecialchars($stream['form_name'] . ' ' . $stream['name']) . '</td>';

                // render periods
                $prevWasDouble = false;
                for ($p = 1; $p <= $periodsPerDay; $p++) {
                    $slot = $allTimetables[$stream['id']][$day][$p] ?? null;

                    if ($prevWasDouble) {
                        // This is the continuation (2/2)
                        if ($slot && $slot['subject_id']) {
                            echo '<td>' . htmlspecialchars($slot['subject_name']) . ' <span class="badge">Double (2/2)</span><br>' . htmlspecialchars($slot['teacher_name']) . '</td>';
                        } else {
                            echo '<td>Free</td>';
                        }
                        $prevWasDouble = false;
                        continue;
                    }

                    if (!$slot) {
                        echo '<td>Free</td>';
                        continue;
                    }

                    if (!empty($slot['is_break'])) {
                        echo '<td><strong>BREAK</strong></td>';
                        continue;
                    }

                    if (!empty($slot['is_special'])) {
                        echo '<td><strong>' . htmlspecialchars($slot['special_name'] ?? 'SPECIAL') . '</strong></td>';
                        continue;
                    }

                    if (!empty($slot['subject_id'])) {
                        if (!empty($slot['is_double_period'])) {
                            // First half of double
                            echo '<td>' . htmlspecialchars($slot['subject_name']) . ' <span class="badge">Double (1/2)</span><br>' . htmlspecialchars($slot['teacher_name']) . '</td>';
                            $prevWasDouble = true;
                        } else {
                            echo '<td>' . htmlspecialchars($slot['subject_name']) . '<br>' . htmlspecialchars($slot['teacher_name']) . '</td>';
                        }
                        continue;
                    }

                    echo '<td>Free</td>';
                }

                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</body></html>';

        exit;
    } else {
        // Fallback CSV for non-excel format (keep existing CSV-like export per stream/day)
        $output = fopen('php://output', 'w');
        fputcsv($output, [$schoolName]);
        fputcsv($output, ['Full School Timetable - All Streams']);
        fputcsv($output, ['Academic Year: ' . getSetting('academic_year', date('Y'))]);
        fputcsv($output, []);

        foreach ($streams as $stream) {
            for ($day = 1; $day <= $schoolDays; $day++) {
                fputcsv($output, [getDayName($day) . ' - ' . $stream['form_name'] . ' ' . $stream['name']]);
                $header = ['Period'];
                for ($period = 1; $period <= $periodsPerDay; $period++) $header[] = 'P' . $period;
                fputcsv($output, $header);

                $row = [$day];
                for ($period = 1; $period <= $periodsPerDay; $period++) {
                    $slot = $allTimetables[$stream['id']][$day][$period] ?? null;
                    if (!$slot) $row[] = 'Free';
                    elseif (!empty($slot['is_break'])) $row[] = 'BREAK';
                    elseif (!empty($slot['is_special'])) $row[] = $slot['special_name'] ?? 'SPECIAL';
                    elseif (!empty($slot['subject_id'])) {
                        $cellContent = $slot['subject_name'];
                        if (!empty($slot['is_double_period'])) $cellContent .= ' (Double)';
                        $cellContent .= ' - ' . $slot['teacher_name'];
                        $row[] = $cellContent;
                    } else $row[] = 'Free';
                }
                fputcsv($output, $row);
                fputcsv($output, []);
            }
        }
        fclose($output);
        exit;
    }
} else {
    // Export single stream (existing logic)
    $stream = getStreamById($streamId);
    if (!$stream) {
        showAlert('Stream not found', 'danger');
        header('Location: view.php');
        exit;
    }

    // Handle PDF export (Dompdf), Excel (HTML) or CSV fallback for single stream
    if ($format === 'pdf') {
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        if (!class_exists('\Dompdf\Dompdf')) {
            // PDF library not installed — fall back to CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="timetable_' . $stream['form_name'] . '_' . $stream['name'] . '.csv"');
        }
        // If Dompdf exists we'll render later
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="timetable_' . $stream['form_name'] . '_' . $stream['name'] . '.xls"');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="timetable_' . $stream['form_name'] . '_' . $stream['name'] . '.csv"');
    }

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

    // If PDF requested and Dompdf available, render HTML and output PDF
    if ($format === 'pdf' && class_exists('\Dompdf\Dompdf')) {
        $html = "<html><head><meta charset='utf-8' /><style>table{border-collapse:collapse;}td,th{border:1px solid #999;padding:6px;font-size:12px;}th{background:#f5f5f5;}</style></head><body>";
        $html .= "<h3>" . htmlspecialchars($schoolName) . "</h3>";
        $html .= "<p>Timetable for " . htmlspecialchars($stream['form_name'] . ' - ' . $stream['name']) . "</p>";
        $html .= '<table><thead><tr><th>Day / Period</th>';
        for ($p = 1; $p <= $periodsPerDay; $p++) $html .= '<th>P' . $p . '</th>';
        $html .= '</tr></thead><tbody>';

        for ($day = 1; $day <= $schoolDays; $day++) {
            $html .= '<tr><th>' . htmlspecialchars(getDayName($day)) . '</th>';
            for ($p = 1; $p <= $periodsPerDay; $p++) {
                $slot = $timetable[$day][$p] ?? null;
                if (!$slot) { $html .= '<td>Free</td>'; continue; }
                if (!empty($slot['is_break'])) { $html .= '<td><strong>BREAK</strong></td>'; continue; }
                if (!empty($slot['is_special'])) { $html .= '<td><strong>' . htmlspecialchars($slot['special_name'] ?? 'SPECIAL') . '</strong></td>'; continue; }
                if (!empty($slot['subject_id'])) {
                    $cell = htmlspecialchars($slot['subject_name']);
                    if (!empty($slot['is_double_period'])) $cell .= ' (Double)';
                    $cell .= '<br>' . htmlspecialchars($slot['teacher_name']);
                    $html .= '<td>' . $cell . '</td>';
                    continue;
                }
                $html .= '<td>Free</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        require_once __DIR__ . '/../vendor/autoload.php';
        $dompdfClass = '\\Dompdf\\Dompdf';
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $filename = 'timetable_' . preg_replace('/[^A-Za-z0-9-_]/', '_', $stream['form_name'] . '_' . $stream['name']) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 1]);
        exit;
    }

    // Create file pointer for CSV/Excel fallback
    $output = fopen('php://output', 'w');

    // Write header
    fputcsv($output, [$schoolName]);
    fputcsv($output, ['Timetable for ' . $stream['form_name'] . ' - ' . $stream['name']]);
    fputcsv($output, ['Academic Year: ' . getSetting('academic_year', date('Y'))]);
    fputcsv($output, []);

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
            } elseif (!empty($slot['is_break'])) {
                $row[] = 'BREAK';
            } elseif (!empty($slot['is_special'])) {
                $row[] = $slot['special_name'] ?? 'SPECIAL';
            } elseif (!empty($slot['subject_id'])) {
                $cellContent = $slot['subject_name'];
                if (!empty($slot['is_double_period'])) {
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
}
