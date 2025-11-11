<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Settings - Busisi Timetable Generator';
$showNav = true;
$isAdmin = true;

$db = getDB();

// Helper function to calculate adjusted period_number for breaks
// User enters logical period (e.g., "after period 4" means after 4 teaching periods)
// DB stores actual slot number accounting for previously-placed breaks
function calculateAdjustedBreakPeriod($userInputPeriod, $excludeBreakId = null) {
    global $db;
    
    // Get all breaks with period_number < adjusted target, ordered by period_number
    $stmt = $db->prepare("SELECT id, period_number FROM break_periods ORDER BY period_number");
    $stmt->execute();
    $existingBreaks = $stmt->fetchAll();
    
    // Count how many breaks come before this one (based on user input)
    $priorBreakCount = 0;
    foreach ($existingBreaks as $b) {
        if ($excludeBreakId && $b['id'] == $excludeBreakId) {
            continue; // Skip the break being edited
        }
        // Calculate the user-facing period for this existing break
        // userFacing = actual_period - priorBreakCount - 1
        // So we reverse: for a given actual_period, we count how many breaks are truly before it
        if ($b['period_number'] - $priorBreakCount - 1 < $userInputPeriod) {
            $priorBreakCount++;
        }
    }
    
    // Adjusted period = user input + prior breaks + 1
    return $userInputPeriod + $priorBreakCount + 1;
}

// Helper function to reverse-calculate user-facing period from DB period_number
function getUserFacingBreakPeriod($dbPeriodNumber) {
    global $db;
    
    // Count how many breaks have period_number < this one
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM break_periods WHERE period_number < ?");
    $stmt->execute([$dbPeriodNumber]);
    $result = $stmt->fetch();
    $priorBreakCount = $result['count'] ?? 0;
    
    // User-facing period = DB period - prior breaks - 1
    return $dbPeriodNumber - $priorBreakCount - 1;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_school':
            $settings = [
                'school_name' => sanitize($_POST['school_name']),
                'school_address' => sanitize($_POST['school_address'] ?? ''),
                'school_phone' => sanitize($_POST['school_phone'] ?? ''),
                'school_email' => sanitize($_POST['school_email'] ?? ''),
                'school_days' => intval($_POST['school_days']),
                'periods_per_day' => intval($_POST['periods_per_day']),
                'period_duration' => intval($_POST['period_duration']),
                'school_start_time' => sanitize($_POST['school_start_time']),
                'academic_year' => sanitize($_POST['academic_year']),
            ];

            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            showAlert('School settings updated successfully', 'success');
            break;

        case 'add_break':
            $name = sanitize($_POST['name']);
            $userInputPeriod = intval($_POST['period_number']);
            $duration = intval($_POST['duration']);

            // Calculate adjusted period_number accounting for existing breaks
            $adjustedPeriodNumber = calculateAdjustedBreakPeriod($userInputPeriod);

            if (createBreakPeriod($name, $adjustedPeriodNumber, $duration)) {
                showAlert('Break period added successfully', 'success');
            } else {
                showAlert('Error adding break period', 'danger');
            }
            break;

        case 'update_break':
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $userInputPeriod = intval($_POST['period_number']);
            $duration = intval($_POST['duration']);

            // Calculate adjusted period_number accounting for other breaks (exclude this one)
            $adjustedPeriodNumber = calculateAdjustedBreakPeriod($userInputPeriod, $id);

            $stmt = $db->prepare("UPDATE break_periods SET name = ?, period_number = ?, duration_minutes = ? WHERE id = ?");
            if ($stmt->execute([$name, $adjustedPeriodNumber, $duration, $id])) {
                showAlert('Break period updated successfully', 'success');
            } else {
                showAlert('Error updating break period', 'danger');
            }
            break;

        case 'delete_break':
            $id = intval($_POST['id']);
            if (deleteBreakPeriod($id)) {
                showAlert('Break period deleted successfully', 'success');
            } else {
                showAlert('Error deleting break period', 'danger');
            }
            break;

        case 'add_special':
            $name = sanitize($_POST['name']);
            $day = intval($_POST['day_of_week']);
            $startPeriod = intval($_POST['start_period']);
            $endPeriod = intval($_POST['end_period']);

            if (createSpecialPeriod($name, $day, $startPeriod, $endPeriod)) {
                showAlert('Special period added successfully', 'success');
            } else {
                showAlert('Error adding special period', 'danger');
            }
            break;

        case 'delete_special':
            $id = intval($_POST['id']);
            if (deleteSpecialPeriod($id)) {
                showAlert('Special period deleted successfully', 'success');
            } else {
                showAlert('Error deleting special period', 'danger');
            }
            break;
    }

    header('Location: settings.php');
    exit;
}

// Get current settings
$schoolSettings = [
    'school_name' => getSetting('school_name', 'Busisi Secondary School'),
    'school_address' => getSetting('school_address', ''),
    'school_phone' => getSetting('school_phone', ''),
    'school_email' => getSetting('school_email', ''),
    'school_days' => getSetting('school_days', '5'),
    'periods_per_day' => getSetting('periods_per_day', '8'),
    'period_duration' => getSetting('period_duration', '40'),
    'school_start_time' => getSetting('school_start_time', '08:00'),
    'academic_year' => getSetting('academic_year', date('Y')),
];

$breakPeriods = getAllBreakPeriods();
$specialPeriods = getAllSpecialPeriods();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-gear"></i> School Settings
        </h2>
    </div>
</div>

<!-- School Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">School Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_school">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="schoolName" class="form-label">School Name *</label>
                            <input type="text" class="form-control" id="schoolName" name="school_name"
                                   value="<?php echo htmlspecialchars($schoolSettings['school_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="academicYear" class="form-label">Academic Year *</label>
                            <input type="text" class="form-control" id="academicYear" name="academic_year"
                                   value="<?php echo htmlspecialchars($schoolSettings['academic_year']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="schoolAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="schoolAddress" name="school_address"
                               value="<?php echo htmlspecialchars($schoolSettings['school_address']); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="schoolPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="schoolPhone" name="school_phone"
                                   value="<?php echo htmlspecialchars($schoolSettings['school_phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="schoolEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="schoolEmail" name="school_email"
                                   value="<?php echo htmlspecialchars($schoolSettings['school_email']); ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Timetable Configuration</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="schoolDays" class="form-label">School Days Per Week *</label>
                            <select class="form-select" id="schoolDays" name="school_days" required>
                                <?php for ($i = 5; $i <= 7; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                            <?php echo $schoolSettings['school_days'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> days
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="periodsPerDay" class="form-label">Periods Per Day *</label>
                            <select class="form-select" id="periodsPerDay" name="periods_per_day" required>
                                <?php for ($i = 6; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                            <?php echo $schoolSettings['periods_per_day'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> periods
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="periodDuration" class="form-label">Period Duration (minutes) *</label>
                            <select class="form-select" id="periodDuration" name="period_duration" required>
                                <?php for ($i = 30; $i <= 60; $i += 5): ?>
                                    <option value="<?php echo $i; ?>"
                                            <?php echo $schoolSettings['period_duration'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> minutes
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="schoolStartTime" class="form-label">School Start Time *</label>
                        <input type="time" class="form-control" id="schoolStartTime" name="school_start_time"
                               value="<?php echo htmlspecialchars($schoolSettings['school_start_time']); ?>" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Break Periods -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Break Periods</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBreakModal">
                    <i class="bi bi-plus-circle"></i> Add Break
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($breakPeriods)): ?>
                    <p class="text-muted mb-0">No break periods configured.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($breakPeriods as $break): 
                            $userFacingPeriod = getUserFacingBreakPeriod($break['period_number']);
                        ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($break['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        After Period <?php echo $userFacingPeriod; ?> (<?php echo $break['duration_minutes']; ?> minutes)
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" 
                                            data-bs-target="#editBreakModal"
                                            data-id="<?php echo $break['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($break['name']); ?>"
                                            data-period="<?php echo $userFacingPeriod; ?>"
                                            data-duration="<?php echo $break['duration_minutes']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete_break">
                                        <input type="hidden" name="id" value="<?php echo $break['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Special Periods -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Special Periods</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSpecialModal">
                    <i class="bi bi-plus-circle"></i> Add Special Period
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Special periods (Sports, Debate, Religion) are common to all streams and will be scheduled automatically.
                </p>
                <?php if (empty($specialPeriods)): ?>
                    <p class="text-muted mb-0">No special periods configured.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($specialPeriods as $special): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($special['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo getDayName($special['day_of_week']); ?>:
                                        Periods <?php echo $special['start_period']; ?>-<?php echo $special['end_period']; ?>
                                    </small>
                                </div>
                                <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="action" value="delete_special">
                                    <input type="hidden" name="id" value="<?php echo $special['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Break Modal -->
<div class="modal fade" id="addBreakModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Break Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_break">
                    <div class="mb-3">
                        <label for="breakName" class="form-label">Break Name *</label>
                        <input type="text" class="form-control" id="breakName" name="name"
                               placeholder="e.g., Short Break, Lunch Break" required>
                    </div>
                    <div class="mb-3">
                        <label for="breakPeriod" class="form-label">After Period Number * <small class="text-muted">(teaching periods)</small></label>
                        <input type="number" class="form-control" id="breakPeriod" name="period_number"
                               min="1" max="12" required>
                    </div>
                    <div class="mb-3">
                        <label for="breakDuration" class="form-label">Duration (minutes) *</label>
                        <input type="number" class="form-control" id="breakDuration" name="duration"
                               min="5" max="60" value="15" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Break</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Break Modal -->
<div class="modal fade" id="editBreakModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Break Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_break">
                    <input type="hidden" name="id" id="editBreakId" value="">
                    <div class="mb-3">
                        <label for="editBreakName" class="form-label">Break Name *</label>
                        <input type="text" class="form-control" id="editBreakName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editBreakPeriod" class="form-label">After Period Number * <small class="text-muted">(teaching periods)</small></label>
                        <input type="number" class="form-control" id="editBreakPeriod" name="period_number"
                               min="1" max="12" required>
                    </div>
                    <div class="mb-3">
                        <label for="editBreakDuration" class="form-label">Duration (minutes) *</label>
                        <input type="number" class="form-control" id="editBreakDuration" name="duration"
                               min="5" max="60" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Break</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Special Period Modal -->
<div class="modal fade" id="addSpecialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Special Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_special">
                    <div class="mb-3">
                        <label for="specialName" class="form-label">Period Name *</label>
                        <input type="text" class="form-control" id="specialName" name="name"
                               placeholder="e.g., Sports, Debate, Religion" required>
                    </div>
                    <div class="mb-3">
                        <label for="specialDay" class="form-label">Day of Week *</label>
                        <select class="form-select" id="specialDay" name="day_of_week" required>
                            <option value="">Select Day</option>
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="7">Sunday</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="specialStart" class="form-label">Start Period *</label>
                            <input type="number" class="form-control" id="specialStart" name="start_period"
                                   min="1" max="12" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="specialEnd" class="form-label">End Period *</label>
                            <input type="number" class="form-control" id="specialEnd" name="end_period"
                                   min="1" max="12" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Special Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle edit break modal population
document.getElementById('editBreakModal')?.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button) {
        document.getElementById('editBreakId').value = button.getAttribute('data-id');
        document.getElementById('editBreakName').value = button.getAttribute('data-name');
        document.getElementById('editBreakPeriod').value = button.getAttribute('data-period');
        document.getElementById('editBreakDuration').value = button.getAttribute('data-duration');
    }
});

// Confirm delete action
function confirmDelete() {
    return confirm('Are you sure you want to delete this item?');
}
</script>

<?php require_once '../includes/footer.php'; ?>
