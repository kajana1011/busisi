<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Dashboard - Busisi Timetable Generator';
$showNav = true;
$isAdmin = true;

// Get statistics
$db = getDB();

$stats = [
    'forms' => $db->query("SELECT COUNT(*) FROM forms")->fetchColumn(),
    'streams' => $db->query("SELECT COUNT(*) FROM streams")->fetchColumn(),
    'subjects' => $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
    'teachers' => $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'assignments' => $db->query("SELECT COUNT(*) FROM subject_assignments")->fetchColumn(),
    'timetables' => $db->query("SELECT COUNT(DISTINCT stream_id) FROM timetables")->fetchColumn(),
];

// Get recent activity
$recentHistory = $db->query("SELECT * FROM generation_history ORDER BY generated_at DESC LIMIT 5")->fetchAll();

// System readiness check
$readiness = [
    'forms' => $stats['forms'] > 0,
    'streams' => $stats['streams'] > 0,
    'subjects' => $stats['subjects'] > 0,
    'teachers' => $stats['teachers'] > 0,
    'assignments' => $stats['assignments'] > 0,
];

$isReady = array_reduce($readiness, function($carry, $item) {
    return $carry && $item;
}, true);

?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h2>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="card bg-primary text-white" style="cursor: pointer;" data-toggle="modal" data-target="#viewModal" onclick="loadItems('forms')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Forms</h6>
                        <h2 class="mb-0"><?php echo $stats['forms']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-collection" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card bg-success text-white" style="cursor: pointer;" data-toggle="modal" data-target="#viewModal" onclick="loadItems('streams')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Streams</h6>
                        <h2 class="mb-0"><?php echo $stats['streams']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-diagram-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card bg-info text-white" style="cursor: pointer;" data-toggle="modal" data-target="#viewModal" onclick="loadItems('subjects')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Subjects</h6>
                        <h2 class="mb-0"><?php echo $stats['subjects']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-book" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card bg-warning text-white" style="cursor: pointer;" data-toggle="modal" data-target="#viewModal" onclick="loadItems('teachers')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Teachers</h6>
                        <h2 class="mb-0"><?php echo $stats['teachers']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-person-badge" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card bg-danger text-white" style="cursor: pointer;" data-toggle="modal" data-target="#viewModal" onclick="loadItems('assignments')">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Assignments</h6>
                        <h2 class="mb-0"><?php echo $stats['assignments']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-link-45deg" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Generated Timetables</h6>
                        <h2 class="mb-0"><?php echo $stats['timetables']; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Readiness -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle"></i> System Readiness
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['forms'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Forms Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['forms'] ? 'success' : 'danger'; ?>">
                            <?php echo $stats['forms']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['streams'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Streams Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['streams'] ? 'success' : 'danger'; ?>">
                            <?php echo $stats['streams']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['subjects'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Subjects Created
                        </span>
                        <span class="badge bg-<?php echo $readiness['subjects'] ? 'success' : 'danger'; ?>">
                            <?php echo $stats['subjects']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['teachers'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Teachers Added
                        </span>
                        <span class="badge bg-<?php echo $readiness['teachers'] ? 'success' : 'danger'; ?>">
                            <?php echo $stats['teachers']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-<?php echo $readiness['assignments'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            Subject Assignments
                        </span>
                        <span class="badge bg-<?php echo $readiness['assignments'] ? 'success' : 'danger'; ?>">
                            <?php echo $stats['assignments']; ?>
                        </span>
                    </div>
                </div>

                <?php if ($isReady): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle-fill"></i>
                        System is ready to generate timetables!
                    </div>
                    <div class="d-grid mt-3">
                        <a href="generate.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-lightning-fill"></i> Generate Timetables
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Complete the setup steps above before generating timetables.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentHistory)): ?>
                    <p class="text-muted mb-0">No generation history yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentHistory as $history): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Timetable Generation</h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($history['generated_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $history['status'] === 'success' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($history['status']); ?>
                                    </span>
                                </div>
                                <?php if ($history['notes']): ?>
                                    <p class="mb-0 mt-2 small text-muted"><?php echo htmlspecialchars($history['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="forms.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Form/Stream
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="subjects.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Subject
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="teachers.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Teacher
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="assignments.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Create Assignment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for viewing items -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function loadItems(type) {
        const modalLabel = document.getElementById('viewModalLabel');
        const modalContent = document.getElementById('modalContent');
        
        // Update modal title
        const titles = {
            'forms': 'Available Forms',
            'streams': 'Available Streams',
            'subjects': 'Available Subjects',
            'teachers': 'Available Teachers',
            'assignments': 'Subject Assignments'
        };
        modalLabel.textContent = titles[type] || 'Items';
        
        // Show loading spinner
        modalContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Fetch data via AJAX
        fetch(`ajax/get_items.php?type=${type}`)
            .then(response => response.text())
            .then(data => {
                modalContent.innerHTML = data;
            })
            .catch(error => {
                modalContent.innerHTML = `<div class="alert alert-danger">Error loading items: ${error.message}</div>`;
            });
    }
</script>

<?php require_once '../includes/footer.php'; ?>
