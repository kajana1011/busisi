<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'View Timetables - Busisi Timetable Generator';
$showNav = true;

$db = getDB();

// Get all streams
$streams = getAllStreams();

// Get selected stream
$selectedStreamId = isset($_GET['stream_id']) ? intval($_GET['stream_id']) : ($streams[0]['id'] ?? 0);

// Get timetable for selected stream
$timetable = [];
if ($selectedStreamId) {
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
    $stmt->execute([$selectedStreamId]);
    $results = $stmt->fetchAll();

    foreach ($results as $row) {
        $timetable[$row['day_of_week']][$row['period_number']] = $row;
    }
}

// Get settings
$schoolDays = intval(getSetting('school_days', 5));
$periodsPerDay = intval(getSetting('periods_per_day', 8));
$schoolName = getSetting('school_name', 'Busisi Secondary School');

// Get stream info
$streamInfo = $selectedStreamId ? getStreamById($selectedStreamId) : null;
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-table"></i> View & Edit Timetables
        </h2>
    </div>
</div>

<!-- Stream Selector -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label for="streamSelect" class="form-label fw-bold">Select Stream:</label>
                        <select class="form-select" id="streamSelect" onchange="window.location.href='view.php?stream_id='+this.value">
                            <?php if (empty($streams)): ?>
                                <option value="">No streams available</option>
                            <?php else: ?>
                                <?php foreach ($streams as $stream): ?>
                                    <option value="<?php echo $stream['id']; ?>"
                                            <?php echo $selectedStreamId == $stream['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($stream['form_name'] . ' - ' . $stream['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="exports.php?stream_id=<?php echo $selectedStreamId; ?>" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                        <a href="exports.php?stream_id=<?php echo $selectedStreamId; ?>&format=pdf" class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($timetable)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                No timetable generated yet. Please <a href="generate.php">generate timetables</a> first.
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Timetable Header -->
    <div class="row mb-3 no-print">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Drag and Drop:</strong> You can drag periods to swap them. The system will warn you if a swap causes a conflict.
            </div>
        </div>
    </div>

    <!-- Timetable Grid -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($schoolName); ?></h4>
                    <p class="mb-0">Timetable for <?php echo htmlspecialchars($streamInfo['form_name'] . ' - ' . $streamInfo['name']); ?></p>
                    <small>Academic Year: <?php echo htmlspecialchars(getSetting('academic_year', date('Y'))); ?></small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">Period</th>
                                    <?php for ($day = 1; $day <= $schoolDays; $day++): ?>
                                        <th class="text-center"><?php echo getDayName($day); ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($period = 1; $period <= $periodsPerDay; $period++): ?>
                                    <tr>
                                        <td class="text-center fw-bold bg-light"><?php echo $period; ?></td>
                                        <?php for ($day = 1; $day <= $schoolDays; $day++): ?>
                                            <?php
                                            $slot = $timetable[$day][$period] ?? null;
                                            $isBreak = $slot && $slot['is_break'];
                                            $isSpecial = $slot && $slot['is_special'];
                                            $isDouble = $slot && $slot['is_double_period'];

                                            $cellClass = 'timetable-cell';
                                            if ($isBreak) {
                                                $cellClass .= ' period-break';
                                            } elseif ($isSpecial) {
                                                $cellClass .= ' period-special';
                                            } elseif ($isDouble) {
                                                $cellClass .= ' period-double';
                                            }

                                            if (!$isBreak && !$isSpecial && $slot && $slot['subject_id']) {
                                                $cellClass .= ' draggable';
                                            }
                                            ?>
                                            <td class="<?php echo $cellClass; ?>"
                                                <?php if (!$isBreak && !$isSpecial && $slot && $slot['subject_id']): ?>
                                                    draggable="true"
                                                    data-stream-id="<?php echo $selectedStreamId; ?>"
                                                    data-day="<?php echo $day; ?>"
                                                    data-period="<?php echo $period; ?>"
                                                    data-subject-id="<?php echo $slot['subject_id']; ?>"
                                                    data-teacher-id="<?php echo $slot['teacher_id']; ?>"
                                                    data-is-double="<?php echo $isDouble ? '1' : '0'; ?>"
                                                <?php endif; ?>>
                                                <?php if ($isBreak): ?>
                                                    <strong>BREAK</strong>
                                                <?php elseif ($isSpecial): ?>
                                                    <strong><?php echo strtoupper(htmlspecialchars($slot['special_name'] ?? 'SPECIAL')); ?></strong>
                                                <?php elseif ($slot && $slot['subject_id']): ?>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($slot['subject_name']); ?>
                                                        <?php if ($isDouble): ?>
                                                            <span class="badge bg-info ms-1">Double</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        <?php echo htmlspecialchars($slot['teacher_name']); ?>
                                                    </small>
                                                    <?php if ($slot['subject_code']): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($slot['subject_code']); ?>)</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Free</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Legend:</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div>
                            <span class="badge bg-warning text-dark">Break</span> Break Period
                        </div>
                        <div>
                            <span class="badge" style="background-color: #a855f7;">Special</span> Special Period (Sports, Debate, Religion)
                        </div>
                        <div>
                            <span class="badge bg-info">Double</span> Double Period (consecutive)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
@media print {
    .no-print, .navbar, footer, .btn, .alert {
        display: none !important;
    }
    .card {
        border: none;
        box-shadow: none;
    }
    .table {
        font-size: 0.85rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
