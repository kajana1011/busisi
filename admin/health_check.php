<?php
/**
 * Database Health Check
 * Quick diagnostic script to check database health and common issues
 */

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = getDB();
$checks = [];

// Check 1: Database connection
try {
    $stmt = $db->query("SELECT 1");
    $checks['Connection'] = ['status' => 'pass', 'message' => 'Database connection successful'];
} catch (Exception $e) {
    $checks['Connection'] = ['status' => 'fail', 'message' => 'Database connection failed: ' . $e->getMessage()];
}

// Check 2: Required tables
$requiredTables = [
    'admins', 'forms', 'streams', 'subjects', 'teachers', 
    'subject_assignments', 'school_settings', 'special_periods', 
    'break_periods', 'timetables', 'generation_history'
];

try {
    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($requiredTables, $existingTables);
    if (empty($missingTables)) {
        $checks['Tables'] = ['status' => 'pass', 'message' => 'All required tables exist'];
    } else {
        $checks['Tables'] = ['status' => 'fail', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
    }
} catch (Exception $e) {
    $checks['Tables'] = ['status' => 'fail', 'message' => 'Could not verify tables: ' . $e->getMessage()];
}

// Check 3: Key table structures
$tableChecks = [
    'subjects' => ['name', 'code', 'description'],
    'teachers' => ['first_name', 'last_name', 'email'],
    'forms' => ['name', 'display_order'],
    'streams' => ['form_id', 'name'],
    'timetables' => ['stream_id', 'day_of_week', 'period_number', 'is_double_period']
];

foreach ($tableChecks as $table => $requiredColumns) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingCols = array_diff($requiredColumns, $columns);
        if (empty($missingCols)) {
            $checks["Table: $table"] = ['status' => 'pass', 'message' => 'All required columns exist'];
        } else {
            $checks["Table: $table"] = ['status' => 'fail', 'message' => 'Missing columns: ' . implode(', ', $missingCols)];
        }
    } catch (Exception $e) {
        $checks["Table: $table"] = ['status' => 'fail', 'message' => 'Table check failed: ' . $e->getMessage()];
    }
}

// Check 4: Sample data
$dataChecks = [
    'Forms' => 'SELECT COUNT(*) FROM forms',
    'Subjects' => 'SELECT COUNT(*) FROM subjects',
    'Teachers' => 'SELECT COUNT(*) FROM teachers',
    'Admins' => 'SELECT COUNT(*) FROM admins',
];

foreach ($dataChecks as $name => $query) {
    try {
        $stmt = $db->query($query);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $checks[$name] = ['status' => 'pass', 'message' => "$count records found"];
        } else {
            $checks[$name] = ['status' => 'warn', 'message' => 'No records found - consider running initial data setup'];
        }
    } catch (Exception $e) {
        $checks[$name] = ['status' => 'fail', 'message' => 'Query failed'];
    }
}

// Check 5: Function availability
$functionChecks = [
    'createSubject',
    'getAllSubjects',
    'getStreamById',
    'createForm',
    'getAssignedStreamsForSubject'
];

foreach ($functionChecks as $func) {
    if (function_exists($func)) {
        $checks["Function: $func"] = ['status' => 'pass', 'message' => 'Function exists'];
    } else {
        $checks["Function: $func"] = ['status' => 'fail', 'message' => 'Function not found'];
    }
}

// Determine overall status
$passCount = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));
$failCount = count(array_filter($checks, fn($c) => $c['status'] === 'fail'));
$warnCount = count(array_filter($checks, fn($c) => $c['status'] === 'warn'));

$overallStatus = 'pass';
if ($failCount > 0) $overallStatus = 'fail';
elseif ($warnCount > 0) $overallStatus = 'warn';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Health Check - Busisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .check-item {
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #dee2e6;
            border-radius: 4px;
        }
        .check-item.pass {
            background-color: #f0f6f0;
            border-color: #28a745;
        }
        .check-item.fail {
            background-color: #f8f5f5;
            border-color: #dc3545;
        }
        .check-item.warn {
            background-color: #fff8f0;
            border-color: #ffc107;
        }
        .badge-status {
            font-size: 0.85rem;
        }
        .header-stat {
            text-align: center;
            padding: 1rem;
        }
        .header-stat h3 {
            margin: 0;
            font-size: 2rem;
        }
        .header-stat p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-heart-pulse"></i> Database Health Check</h3>
                        <small>Diagnostic report for database and system integrity</small>
                    </div>

                    <!-- Overall Status -->
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3 header-stat bg-success text-white rounded">
                                <h3><?php echo $passCount; ?></h3>
                                <p>Passed</p>
                            </div>
                            <div class="col-md-3 header-stat bg-danger text-white rounded">
                                <h3><?php echo $failCount; ?></h3>
                                <p>Failed</p>
                            </div>
                            <div class="col-md-3 header-stat bg-warning text-dark rounded">
                                <h3><?php echo $warnCount; ?></h3>
                                <p>Warnings</p>
                            </div>
                            <div class="col-md-3 header-stat bg-info text-white rounded">
                                <h3><?php echo count($checks); ?></h3>
                                <p>Total Checks</p>
                            </div>
                        </div>

                        <!-- Overall Status Badge -->
                        <div class="mb-4 text-center">
                            <?php if ($overallStatus === 'pass'): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="bi bi-check-circle-fill"></i> <strong>System Status: OK</strong>
                                    <p class="mb-0 mt-2">All critical checks passed. Your database is healthy.</p>
                                </div>
                            <?php elseif ($overallStatus === 'fail'): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>System Status: CRITICAL</strong>
                                    <p class="mb-0 mt-2">Some critical checks failed. Please address the issues below.</p>
                                    <p class="mb-0 mt-2">
                                        <a href="sync_schema.php" class="alert-link">Go to Database Sync Utility</a>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning" role="alert">
                                    <i class="bi bi-exclamation-circle-fill"></i> <strong>System Status: WARNING</strong>
                                    <p class="mb-0 mt-2">Some checks require attention. Review warnings below.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Detailed Checks -->
                        <h5 class="mb-3">Detailed Results:</h5>
                        <?php foreach ($checks as $name => $check): ?>
                            <div class="check-item <?php echo $check['status']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($check['status'] === 'pass'): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php elseif ($check['status'] === 'fail'): ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php else: ?>
                                                <i class="bi bi-exclamation-circle-fill text-warning"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($name); ?>
                                        </h6>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars($check['message']); ?></p>
                                    </div>
                                    <span class="badge badge-status bg-<?php echo $check['status'] === 'pass' ? 'success' : ($check['status'] === 'fail' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($check['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Actions -->
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Quick Actions:</h5>
                                <a href="sync_schema.php" class="btn btn-primary btn-block mb-2 w-100">
                                    <i class="bi bi-database"></i> Database Sync Utility
                                </a>
                                <button class="btn btn-secondary btn-block w-100" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Report
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Documentation:</h5>
                                <a href="<?php echo file_exists('../DATABASE_SYNC_GUIDE.md') ? '../DATABASE_SYNC_GUIDE.md' : '#'; ?>" class="btn btn-info btn-block mb-2 w-100">
                                    <i class="bi bi-book"></i> Sync Guide
                                </a>
                                <a href="index.php" class="btn btn-secondary btn-block w-100">
                                    <i class="bi bi-house"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="alert alert-info mt-4" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Need Help?</h6>
                    <p class="mb-2">If you see failures above:</p>
                    <ol class="mb-0">
                        <li>Check <strong>DATABASE_SYNC_GUIDE.md</strong> in the project root</li>
                        <li>Use the <strong>Database Sync Utility</strong> to fix schema issues</li>
                        <li>Verify your database credentials in <code>config/database.php</code></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
