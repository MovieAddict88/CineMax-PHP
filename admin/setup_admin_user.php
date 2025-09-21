<?php
require_once __DIR__ . '/../db.php';

// Create users table
$sql = "
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
";
if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert a default admin user if it doesn't exist
$admin_user = 'admin';
$admin_pass = 'password'; // The password to be hashed
$hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);

// Check if admin user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $admin_user);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    // Insert the new admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin_user, $hashed_password);
    if ($stmt->execute()) {
        echo "Default admin user created successfully.<br>";
        echo "Username: " . $admin_user . "<br>";
        echo "Password: " . $admin_pass . "<br>";
    } else {
        echo "Error creating admin user: " . $stmt->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

$stmt->close();
$conn->close();
?>
