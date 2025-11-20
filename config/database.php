<?php
/**
 * Database Configuration
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'busisi');
define('DB_USER', 'busisiadmin');
define('DB_PASS', 'admin123');
define('DB_CHARSET', 'utf8mb4');

// Display errors (set to false in production)
define('DISPLAY_ERRORS', true);

// Timezone
define('TIMEZONE', 'Africa/Nairobi');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
