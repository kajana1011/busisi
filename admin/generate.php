<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Generate Timetables - Busisi Timetable Generator';
$showNav = true;

$db = getDB();

// Check system readiness
$readiness = [
    'forms' => $db->query("SELECT COUNT(*) FROM forms")->fetchColumn() > 0,
    'streams' => $db->query("SELECT COUNT(*) FROM streams")->fetchColumn() > 0,
    'subjects' => $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn() > 0,
    'teachers' => $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn() > 0,
    'assignments' => $db->query("SELECT COUNT(*) FROM subject_assignments")->fetchColumn() > 0,
    'settings' => getSetting('school_days') && getSetting('periods_per_day'),
];

$isReady = array_reduce($readiness, function($carry, $item) {
    return $carry && $item;
}, true);

// Get statistics
$stats = [
    'streams' => $db->query("SELECT COUNT(*) FROM streams")->fetchColumn(),
    'assignments' => $db->query("SELECT COUNT(*) FROM subject_assignments")->fetchColumn(),
    'school_days' => getSetting('school_days', 5),
    'periods_per_day' => getSetting('periods_per_day', 8),
];

// Get recent generations
$recentGenerations = $db->query("SELECT gh.*, a.username
                                FROM generation_history gh
                                LEFT JOIN admins a ON gh.generated_by = a.id
                                ORDER BY gh.generated_at DESC
                                LIMIT 5")->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-lightning"></i> Generate Timetables
        </h2>
        <p class="lead text-muted">
            Automatically generate conflict-free timetables for all streams
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- System Readiness -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">System Readiness Check</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['forms'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Forms Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['forms'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['forms'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['streams'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Streams Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['streams'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['streams'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['subjects'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Subjects Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['subjects'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['subjects'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['teachers'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Teachers Added
                        </span>
                        <span class="badge bg-<?php echo $readiness['teachers'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['teachers'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['assignments'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Subject Assignments Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['assignments'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['assignments'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['settings'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            School Settings Configured
                        </span>
                        <span class="badge bg-<?php echo $readiness['settings'] ? 'success' : 'danger'; ?>">
                            <?php echo $readiness['settings'] ? 'Ready' : 'Not Ready'; ?>
                        </span>
                    </div>
                </div>

                <?php if ($isReady): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>All systems ready!</strong> You can now generate timetables.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>System not ready.</strong> Please complete all the requirements above before generating timetables.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Generation Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Generation Settings</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Number of Streams</label>
                        <input type="text" class="form-control" value="<?php echo $stats['streams']; ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Total Assignments</label>
                        <input type="text" class="form-control" value="<?php echo $stats['assignments']; ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">School Days per Week</label>
                        <input type="text" class="form-control" value="<?php echo $stats['school_days']; ?> days" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Periods per Day</label>
                        <input type="text" class="form-control" value="<?php echo $stats['periods_per_day']; ?> periods" readonly>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <h6><i class="bi bi-info-circle-fill"></i> Generation Features:</h6>
                    <ul class="mb-0">
                        <li>Automatic conflict detection - no teacher double-booking</li>
                        <li>Even distribution of periods throughout the week</li>
                        <li>Double and single period support</li>
                        <li>Respect for break and special periods</li>
                        <li>Randomized generation for variety</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Generate Button -->
        <?php if ($isReady): ?>
            <div class="card">
                <div class="card-body text-center py-4">
                    <h4 class="mb-3">Ready to Generate?</h4>
                    <p class="text-muted mb-4">
                        This will create timetables for all <?php echo $stats['streams']; ?> streams.
                        Any existing timetables will be replaced.
                    </p>
                    <button type="button" id="generateBtn" class="btn btn-primary btn-lg" onclick="generateTimetable()">
                        <i class="bi bi-lightning-fill"></i> Generate Timetables
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Recent Generations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Generations</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentGenerations)): ?>
                    <p class="text-muted mb-0">No generation history yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentGenerations as $gen): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="badge bg-<?php echo $gen['status'] === 'success' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($gen['status']); ?>
                                            </span>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($gen['generated_at'])); ?>
                                        </small>
                                        <?php if ($gen['username']): ?>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($gen['username']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($gen['notes']): ?>
                                    <p class="mb-0 mt-2 small"><?php echo htmlspecialchars($gen['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
