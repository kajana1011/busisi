<?php
/**
 * Setup Wizard
 * Run this once to set up the database
 */

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Check if database and tables already exist
require_once 'config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    // Check if all required tables exist
    $requiredTables = ['admins', 'forms', 'streams', 'subjects', 'teachers', 'subject_assignments', 'school_settings', 'special_periods', 'break_periods', 'timetables', 'generation_history'];

    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $allTablesExist = true;
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            $allTablesExist = false;
            break;
        }
    }

    if ($allTablesExist) {
        // Database is already set up, redirect to login
        header('Location: admin/login.php');
        exit;
    }
} catch (PDOException $e) {
    // Database doesn't exist or connection failed, proceed with setup
}

// Step 1: Database Connection Test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    require_once 'config/database.php';

    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $success = "Database connection successful!";
        $step = 2;
    } catch (PDOException $e) {
        $error = "Connection failed: " . $e->getMessage();
    }
}

// Step 2: Create Database and Tables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_database'])) {
    require_once 'config/database.php';

    try {
        // Connect without database
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);

        // Read and execute schema
        $schema = file_get_contents('sql/schema.sql');
        $pdo->exec($schema);

        // Read and execute initial data
        $initialData = file_get_contents('sql/initial_data.sql');
        $pdo->exec($initialData);

        $success = "Database and tables created successfully!";
        $step = 3;
    } catch (PDOException $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Busisi Timetable Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3><i class="bi bi-calendar3"></i> Busisi Timetable Generator</h3>
                        <p class="mb-0">Setup Wizard</p>
                    </div>
                    <div class="card-body">
                        <!-- Progress Steps -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div class="text-center <?php echo $step >= 1 ? 'text-primary' : 'text-muted'; ?>">
                                        <div class="rounded-circle bg-<?php echo $step >= 1 ? 'primary' : 'secondary'; ?> text-white d-inline-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <?php echo $step > 1 ? '<i class="bi bi-check"></i>' : '1'; ?>
                                        </div>
                                        <div class="small mt-2">Connection</div>
                                    </div>
                                    <div class="text-center <?php echo $step >= 2 ? 'text-primary' : 'text-muted'; ?>">
                                        <div class="rounded-circle bg-<?php echo $step >= 2 ? 'primary' : 'secondary'; ?> text-white d-inline-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <?php echo $step > 2 ? '<i class="bi bi-check"></i>' : '2'; ?>
                                        </div>
                                        <div class="small mt-2">Database</div>
                                    </div>
                                    <div class="text-center <?php echo $step >= 3 ? 'text-primary' : 'text-muted'; ?>">
                                        <div class="rounded-circle bg-<?php echo $step >= 3 ? 'primary' : 'secondary'; ?> text-white d-inline-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <?php echo $step > 3 ? '<i class="bi bi-check"></i>' : '3'; ?>
                                        </div>
                                        <div class="small mt-2">Complete</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($step === 1): ?>
                            <h5 class="mb-3">Step 1: Test Database Connection</h5>
                            <p class="text-muted">
                                Make sure you have updated the database credentials in <code>config/database.php</code>
                            </p>
                            <div class="alert alert-info">
                                <strong>Current Settings:</strong><br>
                                <?php
                                if (file_exists('config/database.php')) {
                                    require_once 'config/database.php';
                                    echo "Host: " . DB_HOST . "<br>";
                                    echo "Database: " . DB_NAME . "<br>";
                                    echo "User: " . DB_USER;
                                } else {
                                    echo "Config file not found. Please copy config/database.example.php to config/database.php";
                                }
                                ?>
                            </div>
                            <form method="POST">
                                <button type="submit" name="test_connection" class="btn btn-primary">
                                    <i class="bi bi-plug"></i> Test Connection
                                </button>
                            </form>
                        <?php elseif ($step === 2): ?>
                            <h5 class="mb-3">Step 2: Create Database & Tables</h5>
                            <p class="text-muted">
                                This will create the database and all required tables.
                            </p>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <strong>Warning:</strong> This will create a new database. If it already exists, it will be used.
                            </div>
                            <form method="POST">
                                <button type="submit" name="create_database" class="btn btn-primary">
                                    <i class="bi bi-database-add"></i> Create Database
                                </button>
                            </form>
                        <?php elseif ($step === 3): ?>
                            <h5 class="mb-3">Setup Complete!</h5>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                The database has been set up successfully!
                            </div>
                            <h6>Default Admin Credentials:</h6>
                            <ul>
                                <li><strong>Username:</strong> admin</li>
                                <li><strong>Password:</strong> admin123</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <strong>Important:</strong> Please change the default password after first login!
                            </div>
                            <a href="admin/login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Go to Login
                            </a>
                            <hr class="my-4">
                            <h6>Next Steps:</h6>
                            <ol>
                                <li>Login with the admin credentials</li>
                                <li>Configure school settings</li>
                                <li>Add forms and streams</li>
                                <li>Add subjects and teachers</li>
                                <li>Create subject assignments</li>
                                <li>Generate timetables</li>
                            </ol>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
