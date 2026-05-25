<?php
require_once __DIR__ . '/../db.php';

$sql2 = "CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)";
if (!mysqli_query($conn, $sql2)) { echo "Error creating notifications table: " . mysqli_error($conn) . "\n"; }
if (mysqli_query($conn, $sql)) {
    echo "Successfully added reject_reason column.\n";
} else {
    echo "Error or already added: " . mysqli_error($conn) . "\n";
}
