<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if setup is completed
if (!isSetupCompleted()) {
    header('Location: setup.php');
    exit;
}

// If user is logged in, redirect to admin dashboard
if (isLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

$pageTitle = 'Busisi Secondary School Timetable Generator';
?>
<?php require_once 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold text-primary mb-3">
                <i class="bi bi-calendar3"></i> Busisi Secondary School
            </h1>
            <h2 class="h3 text-muted mb-4">Timetable Generator</h2>
            <p class="lead mb-4">
                A comprehensive web-based system for automatically generating conflict-free timetables
                for secondary schools with advanced features and intuitive management.
            </p>
        </div>
    </div>
</div>

<!-- Features Overview -->
<div class="row mb-5">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-gear text-primary" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Smart Generation</h5>
                <p class="card-text">
                    Automatic conflict-free scheduling with intelligent period distribution,
                    support for single and double periods, and even workload balancing.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-pencil-square text-success" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Easy Editing</h5>
                <p class="card-text">
                    Drag-and-drop interface for timetable modifications with real-time
                    conflict detection and instant validation.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-spreadsheet text-info" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Export & Share</h5>
                <p class="card-text">
                    Export timetables in multiple formats including CSV and Excel.
                    Print-ready layouts for easy distribution.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<?php if (isLoggedIn()): ?>
<div class="row mb-5">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-value"><?php echo count(getAllForms()); ?></div>
                <div class="stats-label">Forms</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-value"><?php echo count(getAllStreams()); ?></div>
                <div class="stats-label">Streams</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-value"><?php echo count(getAllSubjects()); ?></div>
                <div class="stats-label">Subjects</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-value"><?php echo count(getAllTeachers()); ?></div>
                <div class="stats-label">Teachers</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Call to Action -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <?php if (isLoggedIn()): ?>
                    <h4 class="mb-3">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h4>
                    <p class="mb-4">Access the admin panel to manage your school's timetable system.</p>
                    <a href="admin/index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <h4 class="mb-3">Ready to Get Started?</h4>
                    <p class="mb-4">Login to access the comprehensive timetable management system.</p>
                    <a href="admin/login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Login to Admin Panel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Footer Info -->
<div class="row mt-5">
    <div class="col-12">
        <div class="text-center text-muted">
            <small>
                <i class="bi bi-info-circle"></i>
                Built with dedication for Busisi Secondary School •
                Version 1.0.0 •
                <?php echo date('Y'); ?>
            </small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
