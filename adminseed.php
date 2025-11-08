<?php
/**
 * Admin Seeder Script
 * Seeds the default admin user into the database
 */

// Include configuration and database connection
require_once 'config/database.php';
require_once 'includes/db.php';

try {
    $db = getDB();

    // Check if admin already exists
    $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $existingAdmin = $stmt->fetch();

    if ($existingAdmin) {
        echo "Admin user already exists.\n";
    } else {
        // Insert default admin user
        // Password: admin123 (hashed)
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin@busisi.com']);

        echo "Admin user seeded successfully.\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }

} catch (PDOException $e) {
    echo "Error seeding admin: " . $e->getMessage() . "\n";
}
?>
