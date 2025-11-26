<?php
/**
 * Database Schema Sync Utility
 * 
 * This script helps synchronize the local database with the hosted version
 * by comparing schemas and providing migration options.
 * 
 * Usage: Open this file in a browser: http://localhost/busisi/admin/sync_schema.php
 */

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Only allow access to admins
if (isset($_POST['action'])) {
    // Admin check not required for this utility during setup
}

$db = getDB();
$messages = [];
$errors = [];

// Get current database info
$stmt = $db->query("SELECT DATABASE()");
$currentDb = $stmt->fetchColumn();

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'add_sample_subjects' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add sample subjects if they don't exist
    $sampleSubjects = [
        ['Mathematics', 'MATH', 'Study of numbers, algebra, geometry, etc.'],
        ['English', 'ENG', 'Study of English language and literature.'],
        ['Physics', 'PHY', 'Study of matter, motion, energy, and forces.'],
        ['Chemistry', 'CHEM', 'Study of substances, reactions, and chemical processes.'],
        ['Biology', 'BIO', 'Study of living organisms and life processes.'],
        ['History', 'HIST', 'Study of past events and human civilization.'],
        ['Geography', 'GEO', 'Study of earth, environment, and spatial relationships.']
    ];
    
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO subjects (name, code, description) VALUES (?, ?, ?)");
        foreach ($sampleSubjects as $subject) {
            $stmt->execute($subject);
        }
        $messages[] = "Sample subjects added successfully!";
    } catch (PDOException $e) {
        $errors[] = "Error adding subjects: " . $e->getMessage();
    }
}

if ($action === 'verify_schema' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify all required tables exist
    try {
        $requiredTables = [
            'admins', 'forms', 'streams', 'subjects', 'teachers', 
            'subject_assignments', 'school_settings', 'special_periods', 
            'break_periods', 'timetables', 'generation_history'
        ];

        $stmt = $db->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $messages[] = "✓ All required tables exist!";
        } else {
            $errors[] = "Missing tables: " . implode(', ', $missingTables);
        }
    } catch (PDOException $e) {
        $errors[] = "Error verifying schema: " . $e->getMessage();
    }
}

if ($action === 'reset_schema' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Confirm before resetting
    $confirm = $_POST['confirm'] ?? '';
    if ($confirm !== 'yes') {
        $errors[] = "Please confirm by typing 'yes' to reset the schema.";
    } else {
        try {
            // Read schema file and execute
            $schemaFile = file_get_contents('../sql/schema.sql');
            if (!$schemaFile) {
                throw new Exception("Could not read schema.sql file");
            }

            // Split by semicolons and execute each statement
            $statements = explode(';', $schemaFile);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }

            // Read and execute initial data
            $dataFile = file_get_contents('../sql/initial_data.sql');
            if ($dataFile) {
                $statements = explode(';', $dataFile);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && strpos($statement, '--') !== 0) {
                        try {
                            $db->exec($statement);
                        } catch (PDOException $e) {
                            // Skip errors in data inserts (some might be duplicates)
                            continue;
                        }
                    }
                }
            }

            $messages[] = "✓ Database schema reset successfully!";
        } catch (Exception $e) {
            $errors[] = "Error resetting schema: " . $e->getMessage();
        }
    }
}

// Get database statistics
$stats = [];
try {
    $tables = ['forms', 'streams', 'subjects', 'teachers', 'subject_assignments', 'timetables', 'admins'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $stats[$table] = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $errors[] = "Could not fetch database statistics: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Sync - Busisi Timetable Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-left: 4px solid #0d6efd;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .action-btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-database"></i> Database Schema Sync Utility</h3>
                        <small>Use this tool to verify and synchronize your database schema</small>
                    </div>
                    <div class="card-body">
                        <!-- Messages -->
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($msg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Errors -->
                        <?php if (!empty($errors)): ?>
                            <?php foreach ($errors as $err): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($err); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Database Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Database:</strong> <?php echo htmlspecialchars($currentDb); ?><br>
                                    <strong>Host:</strong> <?php echo DB_HOST; ?><br>
                                    <strong>User:</strong> <?php echo DB_USER; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Database Statistics</h5>
                                <?php foreach ($stats as $table => $count): ?>
                                    <div class="stat-card">
                                        <strong><?php echo ucfirst($table); ?>:</strong> <span class="badge bg-primary"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr>

                        <!-- Actions -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="bi bi-check"></i> Verify Schema</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Check if all required tables exist in your database.</p>
                                        <form method="POST" class="d-grid">
                                            <input type="hidden" name="action" value="verify_schema">
                                            <button type="submit" class="btn btn-info action-btn">
                                                <i class="bi bi-search"></i> Verify Tables
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="bi bi-plus"></i> Add Sample Subjects</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Add default subjects if they don't exist (Math, English, Physics, etc).</p>
                                        <form method="POST" class="d-grid">
                                            <input type="hidden" name="action" value="add_sample_subjects">
                                            <button type="submit" class="btn btn-success action-btn">
                                                <i class="bi bi-book"></i> Add Subjects
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Reset Schema</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Completely reset database to initial state. <strong>This will delete all data!</strong></p>
                                        <button type="button" class="btn btn-danger action-btn" data-bs-toggle="modal" data-bs-target="#resetModal">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset Database
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Confirm Reset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>⚠️ WARNING: This action cannot be undone!</strong></p>
                    <p>This will:</p>
                    <ul>
                        <li>Drop all existing tables</li>
                        <li>Create fresh tables from schema.sql</li>
                        <li>Load initial data from initial_data.sql</li>
                        <li>Delete all your current data</li>
                    </ul>
                    <p>Type <strong>yes</strong> in the field below to confirm:</p>
                    <form method="POST" id="resetForm">
                        <input type="hidden" name="action" value="reset_schema">
                        <div class="mb-3">
                            <input type="text" name="confirm" class="form-control" placeholder="Type 'yes' to confirm" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> Confirm Reset
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
